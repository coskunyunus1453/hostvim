<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Models\SupportTicket;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Talep')->schema([
                TextInput::make('number')->label('Talep No')->disabled(),
                TextInput::make('user.name')->label('Müşteri')->disabled(),
                TextInput::make('user.email')->label('E-posta')->disabled(),
                TextInput::make('subject')->label('Konu')->disabled()->columnSpanFull(),
                Select::make('department')->label('Departman')->options([
                    'general' => 'Genel',
                    'technical' => 'Teknik',
                    'billing' => 'Fatura',
                    'sales' => 'Satış',
                ]),
                Select::make('priority')->label('Öncelik')->options([
                    'low' => 'Düşük',
                    'medium' => 'Orta',
                    'high' => 'Yüksek',
                ]),
                Select::make('status')->label('Durum')->options([
                    SupportTicket::STATUS_OPEN => 'Açık',
                    SupportTicket::STATUS_ANSWERED => 'Yanıtlandı',
                    SupportTicket::STATUS_CUSTOMER_REPLY => 'Müşteri yanıtı',
                    SupportTicket::STATUS_ON_HOLD => 'Beklemede',
                    SupportTicket::STATUS_CLOSED => 'Kapalı',
                ]),
            ])->columns(2),

            Section::make('Mesajlar')->schema([
                Placeholder::make('messages_thread')
                    ->label('')
                    ->content(function (?SupportTicket $record): HtmlString {
                        if ($record === null) {
                            return new HtmlString('<p class="text-sm text-gray-500">—</p>');
                        }

                        $record->loadMissing('messages.user');
                        $html = '<div class="space-y-3">';
                        foreach ($record->messages as $message) {
                            $author = $message->is_staff ? 'Destek Ekibi' : e($message->user?->name ?? 'Müşteri');
                            $time = $message->created_at?->format('d.m.Y H:i') ?? '';
                            $body = nl2br(e($message->body));
                            $bg = $message->is_staff ? 'background:#fff7ed;border-color:#fed7aa;' : 'background:#f8fafc;border-color:#e2e8f0;';
                            $html .= "<div style=\"border:1px solid;border-radius:12px;padding:12px;{$bg}\">";
                            $html .= "<div style=\"font-size:12px;color:#64748b;margin-bottom:8px;\"><strong>{$author}</strong> · {$time}</div>";
                            $html .= "<div style=\"font-size:14px;color:#0f172a;white-space:pre-wrap;\">{$body}</div>";
                            $html .= '</div>';
                        }
                        $html .= '</div>';

                        return new HtmlString($html);
                    })
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
