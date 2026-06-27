<?php

namespace App\Services;

use App\Models\DomainName;
use App\Models\Order;
use App\Models\OwnershipTransferRequest;
use App\Models\User;
use App\Services\Panel\PanelCustomerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Hesaplar arası domain/hosting sahipliği devri:
 *  - müşteri talep oluşturur (pending)
 *  - admin onaylar/reddeder
 *  - onayda hem store DB hem Panelze sahipliği güncellenir
 */
class OwnershipTransferService
{
    public function __construct(
        private PanelCustomerService $panel,
        private TemplatedMailService $mail,
    ) {}

    /**
     * Müşteri tarafı: domain devir talebi oluştur.
     */
    public function requestDomainTransfer(User $user, DomainName $domain, string $targetEmail, ?string $note): OwnershipTransferRequest
    {
        $this->assertDomainOwner($user, $domain);

        $target = $this->resolveTarget($user, $targetEmail);

        $this->assertNoPendingFor(
            fn ($q) => $q->where('domain_name_id', $domain->id)
        );

        $request = OwnershipTransferRequest::create([
            'number' => OwnershipTransferRequest::generateNumber(),
            'user_id' => $user->id,
            'type' => OwnershipTransferRequest::TYPE_DOMAIN,
            'domain_name_id' => $domain->id,
            'subject_domain' => $domain->domain,
            'target_email' => $target->email,
            'target_user_id' => $target->id,
            'status' => OwnershipTransferRequest::STATUS_PENDING,
            'customer_note' => $note,
        ]);

        $this->sendRequestedEmail($request, $user);

        return $request;
    }

    /**
     * Müşteri tarafı: hosting devir talebi oluştur.
     */
    public function requestHostingTransfer(User $user, Order $order, string $serviceDomain, string $targetEmail, ?string $note): OwnershipTransferRequest
    {
        abort_unless((int) $order->user_id === (int) $user->id, 403);

        $target = $this->resolveTarget($user, $targetEmail);

        $this->assertNoPendingFor(
            fn ($q) => $q->where('order_id', $order->id)
        );

        $request = OwnershipTransferRequest::create([
            'number' => OwnershipTransferRequest::generateNumber(),
            'user_id' => $user->id,
            'type' => OwnershipTransferRequest::TYPE_HOSTING,
            'order_id' => $order->id,
            'subject_domain' => $serviceDomain,
            'target_email' => $target->email,
            'target_user_id' => $target->id,
            'status' => OwnershipTransferRequest::STATUS_PENDING,
            'customer_note' => $note,
        ]);

        $this->sendRequestedEmail($request, $user);

        return $request;
    }

    public function cancel(OwnershipTransferRequest $request): void
    {
        if (! $request->isPending()) {
            return;
        }
        $request->update(['status' => OwnershipTransferRequest::STATUS_CANCELLED]);
    }

    /**
     * Admin tarafı: devri onayla — store DB + Panelze sahipliği güncellenir.
     */
    public function approve(OwnershipTransferRequest $request, ?User $admin = null): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['status' => 'Talep zaten işlenmiş.']);
        }

        $target = $request->targetUser ?? User::query()->whereRaw('LOWER(email) = ?', [strtolower($request->target_email)])->first();
        if ($target === null) {
            throw ValidationException::withMessages(['target_email' => 'Hedef hesap bulunamadı.']);
        }

        $source = $request->user;

        DB::transaction(function () use ($request, $target, $admin): void {
            if ($request->type === OwnershipTransferRequest::TYPE_DOMAIN) {
                $domain = $request->domainName;
                if ($domain !== null) {
                    $domain->update(['customer_email' => $target->email]);
                }
            } else {
                $order = $request->order;
                if ($order !== null) {
                    $order->update([
                        'user_id' => $target->id,
                        'customer_email' => $target->email,
                        'customer_name' => $target->name ?: $order->customer_name,
                    ]);
                }
            }

            $request->update([
                'status' => OwnershipTransferRequest::STATUS_APPROVED,
                'target_user_id' => $target->id,
                'processed_at' => now(),
                'processed_by' => $admin?->id,
            ]);
        });

        // Panelze tarafını senkronla (best-effort; store sahipliği zaten güncellendi).
        $this->syncPanel($request->fresh(), $source, $target);

        $this->sendApprovedEmails($request->fresh(), $source, $target);
    }

    public function reject(OwnershipTransferRequest $request, string $reason, ?User $admin = null): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['status' => 'Talep zaten işlenmiş.']);
        }

        $request->update([
            'status' => OwnershipTransferRequest::STATUS_REJECTED,
            'admin_note' => $reason,
            'processed_at' => now(),
            'processed_by' => $admin?->id,
        ]);

        $this->sendRejectedEmail($request, $request->user, $reason);
    }

    private function syncPanel(OwnershipTransferRequest $request, ?User $source, User $target): void
    {
        if ($source === null) {
            $request->update(['panel_sync_error' => 'Kaynak hesap bulunamadı.']);

            return;
        }

        try {
            $result = $this->panel->transferOwnership($source, [
                'type' => $request->type,
                'domain' => (string) $request->subject_domain,
                'target_email' => $target->email,
            ]);

            if (! empty($result['target_panel_user_id']) && ! $target->panel_user_id) {
                $target->forceFill(['panel_user_id' => (int) $result['target_panel_user_id']])->save();
            }

            $request->update(['panel_synced' => true, 'panel_sync_error' => null]);
        } catch (Throwable $e) {
            $request->update(['panel_sync_error' => Str::limit($e->getMessage(), 480)]);
        }
    }

    private function resolveTarget(User $user, string $targetEmail): User
    {
        $targetEmail = strtolower(trim($targetEmail));

        if ($targetEmail === '' || $targetEmail === strtolower((string) $user->email)) {
            throw ValidationException::withMessages([
                'target_email' => 'Devralacak hesabın e-postası kendi hesabınızdan farklı olmalıdır.',
            ]);
        }

        $target = User::query()->whereRaw('LOWER(email) = ?', [$targetEmail])->first();
        if ($target === null) {
            throw ValidationException::withMessages([
                'target_email' => 'Bu e-posta ile kayıtlı bir HostVim hesabı bulunamadı. Önce devralacak kişinin üye olması gerekir.',
            ]);
        }

        return $target;
    }

    private function assertDomainOwner(User $user, DomainName $domain): void
    {
        abort_unless(
            $domain->customer_email !== null && strcasecmp($domain->customer_email, (string) $user->email) === 0,
            403
        );
    }

    private function assertNoPendingFor(callable $scope): void
    {
        $exists = OwnershipTransferRequest::query()
            ->where('status', OwnershipTransferRequest::STATUS_PENDING)
            ->where($scope)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'target_email' => 'Bu varlık için zaten bekleyen bir devir talebi var.',
            ]);
        }
    }

    private function sendRequestedEmail(OwnershipTransferRequest $request, User $user): void
    {
        $this->mail->send('ownership-transfer-requested', $user->email, [
            'customer_name' => $user->name,
            'subject_domain' => (string) $request->subject_domain,
            'transfer_type_label' => $request->typeLabel(),
            'target_email' => $request->target_email,
            'source_email' => (string) $user->email,
            'reason' => '',
        ]);
    }

    private function sendApprovedEmails(OwnershipTransferRequest $request, ?User $source, User $target): void
    {
        $payload = [
            'subject_domain' => (string) $request->subject_domain,
            'transfer_type_label' => $request->typeLabel(),
            'target_email' => $request->target_email,
            'source_email' => (string) ($source?->email ?? ''),
            'reason' => '',
        ];

        if ($source !== null) {
            $this->mail->send('ownership-transfer-approved', $source->email, array_merge($payload, [
                'customer_name' => $source->name,
            ]));
        }

        $this->mail->send('ownership-transfer-approved', $target->email, array_merge($payload, [
            'customer_name' => $target->name,
        ]));
    }

    private function sendRejectedEmail(OwnershipTransferRequest $request, ?User $user, string $reason): void
    {
        if ($user === null) {
            return;
        }

        $this->mail->send('ownership-transfer-rejected', $user->email, [
            'customer_name' => $user->name,
            'subject_domain' => (string) $request->subject_domain,
            'transfer_type_label' => $request->typeLabel(),
            'target_email' => $request->target_email,
            'source_email' => (string) $user->email,
            'reason' => $reason !== '' ? $reason : 'Belirtilmedi',
        ]);
    }
}
