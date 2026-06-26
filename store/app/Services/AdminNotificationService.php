<?php

namespace App\Services;

use App\Filament\Resources\CloudServers\CloudServerResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\DomainNames\DomainNameResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\AdminNotification;
use App\Models\CloudServer;
use App\Models\ContactMessage;
use App\Models\DomainName;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    public function notify(
        string $type,
        string $title,
        ?string $body,
        string $actionUrl,
        ?Model $notifiable = null,
        ?string $dedupeKey = null,
        string $icon = 'heroicon-o-bell',
        string $color = 'primary',
    ): ?AdminNotification {
        if ($dedupeKey !== null) {
            $existing = AdminNotification::query()->where('dedupe_key', $dedupeKey)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        return AdminNotification::query()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'color' => $color,
            'action_url' => $actionUrl,
            'notifiable_type' => $notifiable?->getMorphClass(),
            'notifiable_id' => $notifiable?->getKey(),
            'dedupe_key' => $dedupeKey,
        ]);
    }

    public function unreadCount(): int
    {
        return AdminNotification::query()->unread()->count();
    }

    /**
     * @return Collection<int, AdminNotification>
     */
    public function recent(int $limit = 20): Collection
    {
        return AdminNotification::query()
            ->recent()
            ->limit($limit)
            ->get();
    }

    public function markAsRead(int $id): ?AdminNotification
    {
        $notification = AdminNotification::query()->find($id);
        $notification?->markAsRead();

        return $notification;
    }

    public function markAllAsRead(): int
    {
        return AdminNotification::query()
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function fromContactMessage(ContactMessage $message): void
    {
        $subject = trim((string) ($message->subject ?: 'Konu belirtilmedi'));

        $this->notify(
            type: AdminNotification::TYPE_CONTACT_MESSAGE,
            title: 'Yeni iletişim mesajı',
            body: $message->name.' · '.$subject,
            actionUrl: ContactMessageResource::getUrl('edit', ['record' => $message]),
            notifiable: $message,
            dedupeKey: 'contact_message:'.$message->id,
            icon: 'heroicon-o-envelope',
            color: 'info',
        );
    }

    public function fromSupportTicket(SupportTicket $ticket): void
    {
        $this->notify(
            type: AdminNotification::TYPE_SUPPORT_TICKET_NEW,
            title: 'Yeni destek talebi',
            body: $ticket->number.' · '.$ticket->subject,
            actionUrl: SupportTicketResource::getUrl('edit', ['record' => $ticket]),
            notifiable: $ticket,
            dedupeKey: 'support_ticket_new:'.$ticket->id,
            icon: 'heroicon-o-lifebuoy',
            color: 'warning',
        );
    }

    public function fromSupportTicketReply(SupportTicket $ticket, SupportTicketMessage $message): void
    {
        $this->notify(
            type: AdminNotification::TYPE_SUPPORT_TICKET_REPLY,
            title: 'Destek talebine müşteri yanıtı',
            body: $ticket->number.' · '.$ticket->subject,
            actionUrl: SupportTicketResource::getUrl('edit', ['record' => $ticket]),
            notifiable: $ticket,
            dedupeKey: 'support_ticket_reply:'.$message->id,
            icon: 'heroicon-o-chat-bubble-left-right',
            color: 'danger',
        );
    }

    public function fromOrderCreated(Order $order): void
    {
        $type = $order->payment_status === 'awaiting_transfer'
            ? AdminNotification::TYPE_ORDER_AWAITING_TRANSFER
            : AdminNotification::TYPE_ORDER_NEW;

        $title = $type === AdminNotification::TYPE_ORDER_AWAITING_TRANSFER
            ? 'Havale bekleyen sipariş'
            : 'Yeni sipariş';

        $this->notify(
            type: $type,
            title: $title,
            body: $order->order_number.' · '.$order->customer_name.' · '.$this->money($order->total),
            actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
            notifiable: $order,
            dedupeKey: 'order_new:'.$order->id,
            icon: 'heroicon-o-shopping-cart',
            color: $type === AdminNotification::TYPE_ORDER_AWAITING_TRANSFER ? 'warning' : 'primary',
        );
    }

    public function fromOrderUpdated(Order $order): void
    {
        if ($order->wasChanged('payment_status')) {
            $this->fromOrderPaymentStatusChange($order);
        }

        if ($order->wasChanged('status') && $order->status === 'cancelled') {
            $this->notify(
                type: AdminNotification::TYPE_ORDER_CANCELLED,
                title: 'Sipariş iptal edildi',
                body: $order->order_number.' · '.$order->customer_name,
                actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                notifiable: $order,
                dedupeKey: 'order_cancelled:'.$order->id,
                icon: 'heroicon-o-x-circle',
                color: 'danger',
            );
        }

        if ($order->wasChanged('panel_provision_status') && $order->panel_provision_status === 'failed') {
            $this->notify(
                type: AdminNotification::TYPE_PROVISION_PANEL_FAILED,
                title: 'Panel kurulumu başarısız',
                body: $order->order_number.' · '.($order->panel_provision_error ?: 'Hata detayı yok'),
                actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                notifiable: $order,
                dedupeKey: 'provision_panel_failed:'.$order->id,
                icon: 'heroicon-o-exclamation-triangle',
                color: 'danger',
            );
        }

        if ($order->wasChanged('cloud_provision_status') && $order->cloud_provision_status === 'failed') {
            $this->notify(
                type: AdminNotification::TYPE_PROVISION_CLOUD_FAILED,
                title: 'Bulut sunucu kurulumu başarısız',
                body: $order->order_number.' · '.($order->cloud_provision_error ?: 'Hata detayı yok'),
                actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                notifiable: $order,
                dedupeKey: 'provision_cloud_failed:'.$order->id,
                icon: 'heroicon-o-server',
                color: 'danger',
            );
        }
    }

    private function fromOrderPaymentStatusChange(Order $order): void
    {
        $original = (string) $order->getOriginal('payment_status');
        $current = (string) $order->payment_status;

        if ($current === 'paid') {
            $this->notify(
                type: AdminNotification::TYPE_ORDER_PAID,
                title: 'Ödeme alındı',
                body: $order->order_number.' · '.$order->customer_name.' · '.$this->money($order->total),
                actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                notifiable: $order,
                dedupeKey: 'order_paid:'.$order->id,
                icon: 'heroicon-o-banknotes',
                color: 'success',
            );

            return;
        }

        if ($current === 'awaiting_transfer') {
            $this->notify(
                type: AdminNotification::TYPE_ORDER_AWAITING_TRANSFER,
                title: 'Havale bekleyen ödeme',
                body: $order->order_number.' · '.$order->customer_name.' · '.$this->money($order->total),
                actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                notifiable: $order,
                dedupeKey: 'order_awaiting_transfer:'.$order->id,
                icon: 'heroicon-o-clock',
                color: 'warning',
            );

            return;
        }

        if ($current === 'failed') {
            $this->notify(
                type: AdminNotification::TYPE_ORDER_PAYMENT_FAILED,
                title: 'Ödeme başarısız',
                body: $order->order_number.' · '.$order->customer_name,
                actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                notifiable: $order,
                dedupeKey: 'order_payment_failed:'.$order->id,
                icon: 'heroicon-o-x-circle',
                color: 'danger',
            );

            return;
        }

        if ($original === 'paid' && $current !== 'paid') {
            $this->notify(
                type: AdminNotification::TYPE_ORDER_REFUNDED,
                title: 'Ödeme iade / geri alındı',
                body: $order->order_number.' · '.$order->customer_name,
                actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                notifiable: $order,
                dedupeKey: 'order_refunded:'.$order->id.':'.$current,
                icon: 'heroicon-o-arrow-uturn-left',
                color: 'warning',
            );
        }
    }

    public function fromDomainProvisionFailed(Order $order, string $domain, ?string $message): void
    {
        $this->notify(
            type: AdminNotification::TYPE_PROVISION_DOMAIN_FAILED,
            title: 'Alan adı kaydı başarısız',
            body: $domain.' · '.$order->order_number.' · '.($message ?: 'Hata detayı yok'),
            actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
            notifiable: $order,
            dedupeKey: 'provision_domain_failed:'.$order->id.':'.$domain,
            icon: 'heroicon-o-globe-alt',
            color: 'danger',
        );
    }

    public function fromCloudServerFailed(CloudServer $server): void
    {
        $orderNumber = $server->order?->order_number ?? '#'.$server->order_id;

        $this->notify(
            type: AdminNotification::TYPE_CLOUD_SERVER_FAILED,
            title: 'Sunucu kurulumu başarısız',
            body: ($server->hostname ?: 'Sunucu').' · '.$orderNumber,
            actionUrl: CloudServerResource::getUrl('index'),
            notifiable: $server,
            dedupeKey: 'cloud_server_failed:'.$server->id,
            icon: 'heroicon-o-cpu-chip',
            color: 'danger',
        );
    }

    public function syncPendingItems(): int
    {
        $created = 0;

        ContactMessage::query()
            ->where('is_read', false)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->each(function (ContactMessage $message) use (&$created): void {
                $before = AdminNotification::query()->count();
                $this->fromContactMessage($message);
                if (AdminNotification::query()->count() > $before) {
                    $created++;
                }
            });

        SupportTicket::query()
            ->where('status', SupportTicket::STATUS_CUSTOMER_REPLY)
            ->orderByDesc('last_reply_at')
            ->limit(50)
            ->get()
            ->each(function (SupportTicket $ticket) use (&$created): void {
                $latestCustomerMessage = $ticket->messages()
                    ->where('is_staff', false)
                    ->latest()
                    ->first();

                if ($latestCustomerMessage === null) {
                    return;
                }

                $before = AdminNotification::query()->count();
                $this->fromSupportTicketReply($ticket, $latestCustomerMessage);
                if (AdminNotification::query()->count() > $before) {
                    $created++;
                }
            });

        Order::query()
            ->where('payment_status', 'awaiting_transfer')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->each(function (Order $order) use (&$created): void {
                $before = AdminNotification::query()->count();
                $this->notify(
                    type: AdminNotification::TYPE_ORDER_AWAITING_TRANSFER,
                    title: 'Havale bekleyen ödeme',
                    body: $order->order_number.' · '.$order->customer_name.' · '.$this->money($order->total),
                    actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                    notifiable: $order,
                    dedupeKey: 'order_awaiting_transfer:'.$order->id,
                    icon: 'heroicon-o-clock',
                    color: 'warning',
                );
                if (AdminNotification::query()->count() > $before) {
                    $created++;
                }
            });

        Order::query()
            ->where('payment_status', 'pending')
            ->where('created_at', '<', now()->subDay())
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->each(function (Order $order) use (&$created): void {
                $before = AdminNotification::query()->count();
                $this->notify(
                    type: AdminNotification::TYPE_PAYMENT_EXPIRING,
                    title: 'Ödeme süresi dolmak üzere',
                    body: $order->order_number.' · '.$order->customer_name.' · 24 saatten uzun süredir bekliyor',
                    actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                    notifiable: $order,
                    dedupeKey: 'payment_expiring:'.$order->id,
                    icon: 'heroicon-o-exclamation-circle',
                    color: 'warning',
                );
                if (AdminNotification::query()->count() > $before) {
                    $created++;
                }
            });

        Order::query()
            ->where(function ($query): void {
                $query->where('panel_provision_status', 'failed')
                    ->orWhere('cloud_provision_status', 'failed');
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->each(function (Order $order) use (&$created): void {
                if ($order->panel_provision_status === 'failed') {
                    $before = AdminNotification::query()->count();
                    $this->notify(
                        type: AdminNotification::TYPE_PROVISION_PANEL_FAILED,
                        title: 'Panel kurulumu başarısız',
                        body: $order->order_number.' · '.($order->panel_provision_error ?: 'Hata detayı yok'),
                        actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                        notifiable: $order,
                        dedupeKey: 'provision_panel_failed:'.$order->id,
                        icon: 'heroicon-o-exclamation-triangle',
                        color: 'danger',
                    );
                    if (AdminNotification::query()->count() > $before) {
                        $created++;
                    }
                }

                if ($order->cloud_provision_status === 'failed') {
                    $before = AdminNotification::query()->count();
                    $this->notify(
                        type: AdminNotification::TYPE_PROVISION_CLOUD_FAILED,
                        title: 'Bulut sunucu kurulumu başarısız',
                        body: $order->order_number.' · '.($order->cloud_provision_error ?: 'Hata detayı yok'),
                        actionUrl: OrderResource::getUrl('edit', ['record' => $order]),
                        notifiable: $order,
                        dedupeKey: 'provision_cloud_failed:'.$order->id,
                        icon: 'heroicon-o-server',
                        color: 'danger',
                    );
                    if (AdminNotification::query()->count() > $before) {
                        $created++;
                    }
                }
            });

        CloudServer::query()
            ->where('status', CloudServer::STATUS_FAILED)
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->each(function (CloudServer $server) use (&$created): void {
                $before = AdminNotification::query()->count();
                $this->fromCloudServerFailed($server);
                if (AdminNotification::query()->count() > $before) {
                    $created++;
                }
            });

        DomainName::query()
            ->where('status', 'failed')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->each(function (DomainName $domain) use (&$created): void {
                $before = AdminNotification::query()->count();
                $this->notify(
                    type: AdminNotification::TYPE_PROVISION_DOMAIN_FAILED,
                    title: 'Alan adı kaydı başarısız',
                    body: $domain->domain.' · '.(is_array($domain->meta) ? ($domain->meta['register_error'] ?? 'Hata detayı yok') : 'Hata detayı yok'),
                    actionUrl: DomainNameResource::getUrl('index'),
                    notifiable: $domain,
                    dedupeKey: 'provision_domain_failed:domain:'.$domain->id,
                    icon: 'heroicon-o-globe-alt',
                    color: 'danger',
                );
                if (AdminNotification::query()->count() > $before) {
                    $created++;
                }
            });

        return $created;
    }

    public function dismissForNotifiable(Model $notifiable): void
    {
        AdminNotification::query()
            ->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey())
            ->unread()
            ->update(['read_at' => now()]);
    }

    private function money(float|string $amount): string
    {
        return number_format((float) $amount, 2, ',', '.').' ₺';
    }
}
