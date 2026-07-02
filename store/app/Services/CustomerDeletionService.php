<?php

namespace App\Services;

use App\Models\User;

class CustomerDeletionService
{
    public function canDelete(User $user): bool
    {
        return ! $user->is_admin;
    }

    public function modalDescription(User $user): string
    {
        $orders = $user->orders()->count();
        $tickets = $user->supportTickets()->count();
        $lines = ['Bu müşteri hesabı kalıcı olarak silinecek.'];

        if ($orders > 0) {
            $lines[] = "{$orders} sipariş kaydı sistemde kalır; müşteri bağlantısı kaldırılır.";
        }
        if ($tickets > 0) {
            $lines[] = "{$tickets} destek talebi ve mesajları silinir.";
        }
        if ($user->panel_user_id) {
            $lines[] = 'Panel hosting hesabı silinmez; yalnızca mağaza eşlemesi kaldırılır.';
        }

        $lines[] = 'Bu işlem geri alınamaz.';

        return implode(' ', $lines);
    }

    public function delete(User $user): void
    {
        if (! $this->canDelete($user)) {
            throw new \InvalidArgumentException('Yönetici hesabı silinemez.');
        }

        $user->delete();
    }
}
