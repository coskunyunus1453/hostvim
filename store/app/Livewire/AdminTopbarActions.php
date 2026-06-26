<?php

namespace App\Livewire;

use App\Services\CacheService;
use App\View\Support\LayoutViewData;
use Filament\Notifications\Notification;
use Livewire\Component;

class AdminTopbarActions extends Component
{
    public function clearCache(): void
    {
        $cleared = app(CacheService::class)->clearAll();
        LayoutViewData::reset();

        Notification::make()
            ->title('Önbellek temizlendi')
            ->body(implode(' · ', $cleared))
            ->success()
            ->send();
    }

    public function render()
    {
        return view('livewire.admin-topbar-actions');
    }
}
