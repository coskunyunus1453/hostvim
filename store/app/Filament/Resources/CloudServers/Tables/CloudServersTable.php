<?php

namespace App\Filament\Resources\CloudServers\Tables;

use App\Models\CloudServer;
use App\Services\Cloud\CloudServerManager;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CloudServersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')->label('Sipariş')->searchable()->placeholder('—'),
                TextColumn::make('hostname')->label('Hostname')->searchable(),
                TextColumn::make('provider_api')->label('API')->badge(),
                TextColumn::make('ipv4')->label('IPv4')->copyable()->placeholder('—'),
                TextColumn::make('region')->label('Bölge')->toggleable(),
                TextColumn::make('plan')->label('Plan')->toggleable(),
                TextColumn::make('status')->label('Durum')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'provisioning' => 'Kuruluyor',
                        'pending' => 'Bekliyor',
                        'failed' => 'Başarısız',
                        'destroyed' => 'Silindi',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'provisioning', 'pending' => 'warning',
                        'failed', 'destroyed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('provision_error')
                    ->label('Hata Nedeni')
                    ->state(fn (CloudServer $r): ?string => $r->status === CloudServer::STATUS_FAILED ? ($r->provision_error ?: null) : null)
                    ->color('danger')
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable()
                    ->tooltip(fn (CloudServer $r): ?string => $r->provision_error ?: null),
                TextColumn::make('panel_state')
                    ->label('Panel')
                    ->state(fn (CloudServer $record): string => match ($record->meta['panel_install'] ?? null) {
                        'queued' => 'Kuyrukta',
                        'running' => 'Kuruluyor',
                        'failed' => 'Hata',
                        default => '—',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Kuruluyor', 'Kuyrukta' => 'info',
                        'Hata' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('provisioned_at')->label('Kurulum')->dateTime('d.m.Y H:i')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Durum')->options([
                    'active' => 'Aktif',
                    'provisioning' => 'Kuruluyor',
                    'pending' => 'Bekliyor',
                    'failed' => 'Başarısız',
                    'destroyed' => 'Silindi',
                ]),
                SelectFilter::make('provider_api')->label('Sağlayıcı')->options(fn (): array => CloudServer::query()
                    ->whereNotNull('provider_api')
                    ->distinct()
                    ->pluck('provider_api', 'provider_api')
                    ->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('start')
                        ->label('Başlat')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(fn (CloudServer $record) => self::run($record, fn (CloudServerManager $m) => $m->power($record, 'start'))),
                    Action::make('reboot')
                        ->label('Yeniden Başlat')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (CloudServer $record) => self::run($record, fn (CloudServerManager $m) => $m->power($record, 'reboot'))),
                    Action::make('stop')
                        ->label('Durdur')
                        ->icon('heroicon-o-stop')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (CloudServer $record) => self::run($record, fn (CloudServerManager $m) => $m->power($record, 'stop'))),
                ])
                    ->label('Güç')
                    ->icon('heroicon-o-bolt')
                    ->button(),

                Action::make('resetPassword')
                    ->label('Root Şifre Sıfırla')
                    ->icon('heroicon-o-key')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Sunucunun root şifresi sıfırlanır. Bazı sağlayıcılar yeni şifreyi e-posta ile gönderir.')
                    ->action(fn (CloudServer $record) => self::run($record, fn (CloudServerManager $m) => $m->resetPassword($record))),

                Action::make('rebuild')
                    ->label('Yeniden Kur / OS Değiştir')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Sunucuyu Yeniden Kur')
                    ->modalDescription('DİKKAT: Sunucudaki tüm veriler silinir. İsterseniz farklı bir işletim sistemi seçebilirsiniz.')
                    ->schema([
                        Select::make('image')
                            ->label('İşletim Sistemi')
                            ->helperText('Boş bırakırsanız mevcut işletim sistemi yeniden kurulur (Linode için seçim zorunludur).')
                            ->searchable()
                            ->options(fn (CloudServer $record): array => self::optionList(fn (CloudServerManager $m) => $m->images($record))),
                    ])
                    ->action(fn (array $data, CloudServer $record) => self::run($record, fn (CloudServerManager $m) => $m->rebuild($record, $data['image'] ?? null))),

                Action::make('resize')
                    ->label('Paket Yükselt')
                    ->icon('heroicon-o-arrows-pointing-out')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalDescription('Sunucu planı değiştirilir. Çoğu sağlayıcıda sunucu kısa süre yeniden başlatılır.')
                    ->schema([
                        Select::make('plan')
                            ->label('Yeni Plan')
                            ->required()
                            ->searchable()
                            ->options(fn (CloudServer $record): array => self::optionList(fn (CloudServerManager $m) => $m->plans($record))),
                    ])
                    ->action(fn (array $data, CloudServer $record) => self::run($record, fn (CloudServerManager $m) => $m->resize($record, (string) $data['plan']))),

                Action::make('installPanel')
                    ->label('Panelze Kur')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('Sunucuya SSH ile bağlanılıp Panelze hosting paneli kurulur. Root şifresi kayıtlı olmalıdır.')
                    ->visible(fn (CloudServer $record): bool => $record->status === CloudServer::STATUS_ACTIVE)
                    ->action(fn (CloudServer $record) => self::run($record, fn (CloudServerManager $m) => $m->installPanel($record))),

                Action::make('destroy')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Sunucuyu Sil')
                    ->modalDescription('DİKKAT: Sunucu sağlayıcıda kalıcı olarak silinir. Bu işlem geri alınamaz.')
                    ->action(fn (CloudServer $record) => self::run($record, fn (CloudServerManager $m) => $m->destroy($record))),
            ]);
    }

    /**
     * @param  \Closure(CloudServerManager): array{ok: bool, message: string}  $callback
     */
    private static function run(CloudServer $record, \Closure $callback): void
    {
        if (! $record->external_id) {
            Notification::make()->title('İşlem yapılamadı')->body('Sunucunun sağlayıcı kimliği yok.')->danger()->send();

            return;
        }

        try {
            $result = $callback(app(CloudServerManager::class));
            Notification::make()
                ->title($result['ok'] ? 'İşlem başarılı' : 'İşlem başarısız')
                ->body($result['message'])
                ->{$result['ok'] ? 'success' : 'danger'}()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Hata')->body($e->getMessage())->danger()->send();
        }
    }

    /**
     * @param  \Closure(CloudServerManager): list<array{id: string, label: string}>  $callback
     * @return array<string, string>
     */
    private static function optionList(\Closure $callback): array
    {
        try {
            return collect($callback(app(CloudServerManager::class)))->pluck('label', 'id')->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
