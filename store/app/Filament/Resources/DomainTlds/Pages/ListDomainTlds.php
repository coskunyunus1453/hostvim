<?php

namespace App\Filament\Resources\DomainTlds\Pages;

use App\Filament\Resources\DomainTlds\DomainTldResource;
use App\Services\Domain\DomainPricingSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDomainTlds extends ListRecords
{
    protected static string $resource = DomainTldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('importCatalog')
                ->label('Katalogdan içe aktar')
                ->icon('heroicon-o-rectangle-stack')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Hazır TLD kataloğunu içe aktar')
                ->modalDescription('100+ popüler uzantı yaklaşık Spaceship maliyetleriyle eklenir; satış fiyatı USD kuru ve kâr marjı ile otomatik hesaplanır. Mevcut uzantılara dokunulmaz. Maliyetler YAKLAŞIKTIR — Spaceship hesabınızdan teyit edin.')
                ->modalSubmitActionLabel('İçe aktar')
                ->schema([
                    Toggle::make('activate')
                        ->label('Eklenen uzantılar hemen satışta olsun')
                        ->default(true),
                ])
                ->action(function (array $data, DomainPricingSyncService $sync): void {
                    $result = $sync->importFromCatalog((bool) ($data['activate'] ?? true));
                    Notification::make()
                        ->title('Katalog içe aktarıldı')
                        ->body("Yeni eklenen: {$result['created']} · Zaten var olan (atlanan): {$result['skipped']}")
                        ->success()
                        ->send();
                }),

            Action::make('bulkAdd')
                ->label('Toplu TLD ekle')
                ->icon('heroicon-o-plus-circle')
                ->modalHeading('Toplu TLD ekle')
                ->modalDescription('Her satıra bir uzantı yazın. Maliyet eklemek için virgülle ayırın. Maliyet vermezseniz katalogdan bulunursa kullanılır; bulunmazsa uzantı satışa kapalı eklenir.')
                ->modalSubmitActionLabel('Ekle')
                ->schema([
                    Textarea::make('tlds')
                        ->label('Uzantı listesi')
                        ->required()
                        ->rows(10)
                        ->placeholder(".com\n.io,34.98\n.shop,2.48,USD\n.dev,12.98,USD,20")
                        ->helperText('Biçim: .tld  veya  .tld,maliyet  veya  .tld,maliyet,PARABİRİMİ  veya  .tld,maliyet,PARABİRİMİ,marj%'),
                    Toggle::make('activate')
                        ->label('Maliyeti olan uzantılar hemen satışta olsun')
                        ->default(true),
                ])
                ->action(function (array $data, DomainPricingSyncService $sync): void {
                    $result = $sync->bulkAddFromText((string) ($data['tlds'] ?? ''), (bool) ($data['activate'] ?? true));
                    $body = "Eklenen: {$result['created']} · Atlanan (zaten var): {$result['skipped']}";
                    if ($result['errors'] !== []) {
                        $body .= ' · Hatalar: '.implode(', ', $result['errors']);
                    }
                    Notification::make()
                        ->title('Toplu ekleme tamamlandı')
                        ->body($body)
                        ->success()
                        ->send();
                }),

            Action::make('syncAll')
                ->label('Tüm API\'lerden fiyat çek')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalDescription('Aktif ve yapılandırılmış tüm registrar API\'lerinden fiyatlar çekilir; her TLD için en ucuz toptan fiyat + marj uygulanır.')
                ->action(function (DomainPricingSyncService $sync): void {
                    $result = $sync->syncAll();
                    $body = "Güncellenen TLD: {$result['updated']}, yeni: {$result['created']}";
                    if ($result['errors'] !== []) {
                        $body .= ' | Hatalar: '.implode('; ', $result['errors']);
                    }
                    Notification::make()->title('Fiyat senkronu tamamlandı')->body($body)->success()->send();
                }),
        ];
    }
}
