<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class CreatePanelAdminCommand extends Command
{
    protected $signature = 'panelze:create-admin
                            {email : Admin e-posta}
                            {--password= : Şifre (yoksa sorulur)}
                            {--name=Admin : Görünen ad}';

    protected $description = 'Panel admin kullanıcısı oluşturur veya şifresini günceller';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) ($this->option('password') ?: $this->secret('Şifre'));
        if ($password === '') {
            $this->error('Şifre boş olamaz.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $user = User::query()->create([
                'name' => (string) $this->option('name'),
                'email' => $email,
                // User model `password` => hashed cast — düz metin verilir, bcrypt ile tek kez hashlenir.
                'password' => $password,
                'email_verified_at' => now(),
            ]);
            $this->info("Kullanıcı oluşturuldu: {$email}");
        } else {
            $user->update([
                'password' => $password,
                'name' => (string) $this->option('name'),
            ]);
            $this->info("Kullanıcı güncellendi: {$email}");
        }

        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        if (! $user->hasRole('admin')) {
            $user->assignRole($role);
        }

        $this->call('panelze:sync-abilities', [], $this->output);

        $this->info('Admin rolü atandı.');

        return self::SUCCESS;
    }
}
