<?php

namespace App\Filament\Resources\BusinessExpenses\Pages;

use App\Filament\Resources\BusinessExpenses\BusinessExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessExpense extends CreateRecord
{
    protected static string $resource = BusinessExpenseResource::class;
}
