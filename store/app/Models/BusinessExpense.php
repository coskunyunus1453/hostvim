<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessExpense extends Model
{
    public const CATEGORIES = [
        'hosting_wholesale' => 'Hosting / toptan alım',
        'domain_wholesale' => 'Domain toptan',
        'server_vps' => 'VPS / VDS / sunucu',
        'infrastructure' => 'Altyapı & lisans',
        'marketing' => 'Pazarlama & reklam',
        'personnel' => 'Personel & hizmet',
        'tax_fee' => 'Vergi & resmi gider',
        'other' => 'Diğer',
    ];

    protected $fillable = [
        'expense_date',
        'category',
        'title',
        'description',
        'amount',
        'currency',
        'vendor',
        'reference',
        'is_recurring',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'is_recurring' => 'boolean',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
