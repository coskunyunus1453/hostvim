<?php

namespace App\Filament\Resources\BusinessExpenses\Pages;

use App\Filament\Resources\BusinessExpenses\BusinessExpenseResource;
use Filament\Resources\Pages\EditRecord;

class EditBusinessExpense extends EditRecord
{
    protected static string $resource = BusinessExpenseResource::class;
}
