<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\DomainRegistration;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\DomainService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeE2ETestDataCommand extends Command
{
    protected $signature = 'panel:purge-e2e-test-data
                            {--dry-run : List targets without deleting}
                            {--force : Skip confirmation}';

    protected $description = 'E2E/demo test domainlerini, kullanıcılarını ve ilişkili kayıtları temizler';

    /** @var list<string> */
    private array $domainPatterns = ['demo-%', 'testhost%', 'e2e-%'];

    /** @var list<string> */
    private array $userEmails = ['demo@hostvim.com'];

    public function handle(DomainService $domainService): int
    {
        if (! $this->option('force') && ! $this->option('dry-run')) {
            if (! $this->confirm('Test/demo domain ve kullanıcıları kalıcı olarak silinecek. Devam?', false)) {
                $this->warn('İptal.');

                return self::SUCCESS;
            }
        }

        $domains = $this->testDomains();
        $users = $this->testUsers();

        $this->info('Test domainleri: '.$domains->pluck('name')->implode(', ') ?: '(yok)');
        $this->info('Test kullanıcıları: '.$users->pluck('email')->implode(', ') ?: '(yok)');

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        foreach ($domains as $domain) {
            try {
                $domainService->delete($domain);
                $this->line('Domain silindi: '.$domain->name);
            } catch (\Throwable $e) {
                $this->error('Domain silinemedi '.$domain->name.': '.$e->getMessage());
            }
        }

        foreach ($users as $user) {
            $this->purgeUser($user);
            $this->line('Kullanıcı silindi: '.$user->email);
        }

        DomainRegistration::query()
            ->where(function ($q): void {
                foreach ($this->domainPatterns as $pattern) {
                    $q->orWhere('domain', 'like', $pattern);
                }
            })
            ->delete();

        $this->info('Temizlik tamamlandı.');

        return self::SUCCESS;
    }

    private function testDomains()
    {
        return Domain::query()
            ->where(function ($q): void {
                foreach ($this->domainPatterns as $pattern) {
                    $q->orWhere('name', 'like', $pattern);
                }
            })
            ->get();
    }

    private function testUsers()
    {
        return User::query()
            ->where(function ($q): void {
                $q->where('email', 'like', '%@example.com')
                    ->orWhere('email', 'like', '%@test.com');
                foreach ($this->userEmails as $email) {
                    $q->orWhere('email', $email);
                }
            })
            ->whereDoesntHave('roles', fn ($r) => $r->where('name', 'admin'))
            ->get();
    }

    private function purgeUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();

            foreach ($user->domains()->get() as $domain) {
                try {
                    app(DomainService::class)->delete($domain);
                } catch (\Throwable) {
                    $domain->delete();
                }
            }

            SupportTicket::query()->where('user_id', $user->id)->each(function (SupportTicket $ticket): void {
                $ticket->messages()->delete();
                $ticket->delete();
            });

            foreach ($user->orders()->get() as $order) {
                $order->items()->delete();
                $order->delete();
            }

            foreach ($user->invoices()->get() as $invoice) {
                $invoice->items()->delete();
                $invoice->delete();
            }

            $user->subscriptions()->delete();
            DomainRegistration::query()->where('user_id', $user->id)->delete();
            $user->cronJobs()->delete();
            $user->ftpAccounts()->delete();
            $user->emailAccounts()->delete();
            $user->backups()->delete();

            $user->delete();
        });
    }
}
