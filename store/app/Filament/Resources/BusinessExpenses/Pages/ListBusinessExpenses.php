<?php

namespace App\Filament\Resources\BusinessExpenses\Pages;

use App\Filament\Resources\BusinessExpenses\BusinessExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessExpenses extends ListRecords
{
    protected static string $resource = BusinessExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Gider Ekle')];
    }
}
