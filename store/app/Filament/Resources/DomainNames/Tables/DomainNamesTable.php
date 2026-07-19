<?php

namespace App\Filament\Resources\DomainNames\Tables;

use App\Models\DomainName;
use App\Services\Domain\DomainProvisioningService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DomainNamesTable
{
    private const DNS_TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'CAA', 'SRV', 'ALIAS'];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')->label('Domain')->searchable()->sortable()->weight('bold'),
                TextColumn::make('registrar_api')->label('Sağlayıcı')->badge(),
                TextColumn::make('status')->label('Durum')->badge()->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'registered', 'active' => 'Kayıtlı',
                        'registering' => 'Kaydediliyor',
                        'pendingTransfer' => 'Transfer Bekliyor',
                        'pending' => 'Beklemede',
                        'expired' => 'Süresi Doldu',
                        'failed' => 'Başarısız',
                        default => $state ?: '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'registered', 'active' => 'success',
                        'registering' => 'info',
                        'pendingTransfer', 'pending' => 'warning',
                        'expired', 'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('register_error')
                    ->label('Hata Nedeni')
                    ->state(fn (DomainName $r): ?string => $r->status === 'failed' && is_array($r->meta) ? ($r->meta['register_error'] ?? null) : null)
                    ->color('danger')
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable()
                    ->tooltip(fn (DomainName $r): ?string => is_array($r->meta) ? ($r->meta['register_error'] ?? null) : null),
                TextColumn::make('expires_at')->label('Bitiş')->date('d.m.Y')->sortable()->placeholder('—')
                    ->color(fn (DomainName $r): string => $r->expires_at && $r->expires_at->isBefore(now()->addDays(30)) ? 'danger' : 'gray'),
                IconColumn::make('auto_renew')->label('Oto. Yenile')->boolean()->toggleable(),
                IconColumn::make('privacy')->label('Gizlilik')->boolean()
                    ->state(fn (DomainName $r): bool => $r->privacy === 'high')->toggleable(),
                IconColumn::make('locked')->label('Kilit')->boolean()->toggleable(),
                TextColumn::make('ns_provider')->label('NS')->badge()->placeholder('—')->toggleable(),
                TextColumn::make('last_synced_at')->label('Senkron')->since()->toggleable()->placeholder('—'),
            ])
            ->defaultSort('expires_at', 'asc')
            ->filters([
                SelectFilter::make('status')->label('Durum')->options([
                    'registered' => 'Kayıtlı',
                    'registering' => 'Kaydediliyor',
                    'failed' => 'Başarısız',
                    'expired' => 'Süresi Doldu',
                    'pendingTransfer' => 'Transfer Bekliyor',
                ]),
                SelectFilter::make('registrar_api')->label('Sağlayıcı')->options(fn (): array => DomainName::query()
                    ->whereNotNull('registrar_api')
                    ->distinct()
                    ->pluck('registrar_api', 'registrar_api')
                    ->all()),
            ])
            ->recordActions([
                Action::make('retryRegister')
                    ->label('Tekrar Kaydet')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('warning')
                    ->visible(fn (DomainName $r): bool => $r->status === 'failed')
                    ->requiresConfirmation()
                    ->modalHeading(fn (DomainName $record): string => $record->domain.' — yeniden kaydet')
                    ->modalDescription('Alan adı sağlayıcıda (Spaceship) yeniden kaydedilmeye çalışılır. Kayıt ücreti Spaceship bakiyenizden düşer. Bakiye yetersizse kayıt yine başarısız olur.')
                    ->action(function (DomainName $record): void {
                        $order = $record->order;
                        if ($order === null) {
                            Notification::make()->title('Sipariş bağlantısı yok')->body('Bu domaine bağlı sipariş bulunamadı, manuel kayıt gerekir.')->danger()->send();

                            return;
                        }

                        try {
                            // Aynı sipariş üzerinden retry: müşteri e-postası + panel senkronu; yeni sipariş yok.
                            $summary = app(DomainProvisioningService::class)->retry($order);
                            $ok = $summary['succeeded'] > 0;
                            Notification::make()
                                ->title($ok ? 'Kayıt başarılı' : 'Kayıt başarısız')
                                ->body(implode("\n", $summary['messages'] ?: [$record->domain]))
                                ->{$ok ? 'success' : 'danger'}()
                                ->persistent()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Hata')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('dns')
                    ->label('DNS Kayıtları')
                    ->icon('heroicon-o-server-stack')
                    ->color('primary')
                    ->modalHeading(fn (DomainName $record): string => $record->domain.' — DNS Kayıtları')
                    ->modalDescription('Kayıtları düzenleyip kaydedin. Silinen satırlar sağlayıcıdan da kaldırılır.')
                    ->modalSubmitActionLabel('Kaydet')
                    ->fillForm(fn (DomainName $record): array => ['records' => self::loadDns($record)])
                    ->schema([
                        Repeater::make('records')
                            ->label('Kayıtlar')
                            ->addActionLabel('Kayıt ekle')
                            ->columns(12)
                            ->schema([
                                Select::make('type')->label('Tip')->options(array_combine(self::DNS_TYPES, self::DNS_TYPES))
                                    ->default('A')->required()->columnSpan(2),
                                TextInput::make('name')->label('Ad')->default('@')->required()->columnSpan(3)
                                    ->helperText('@ = kök'),
                                TextInput::make('value')->label('Değer')->required()->columnSpan(4),
                                TextInput::make('priority')->label('Öncelik')->numeric()->columnSpan(1)
                                    ->helperText('MX'),
                                TextInput::make('ttl')->label('TTL')->numeric()->default(3600)->columnSpan(2),
                            ])
                            ->defaultItems(0),
                    ])
                    ->action(fn (array $data, DomainName $record) => self::run($record, fn (DomainManagementService $s) => $s->saveDnsRecords($record, $data['records'] ?? []))),

                Action::make('nameservers')
                    ->label('Nameserver')
                    ->icon('heroicon-o-globe-alt')
                    ->color('gray')
                    ->fillForm(fn (DomainName $record): array => [
                        'provider' => $record->ns_provider ?: 'basic',
                        'hosts' => $record->nameservers ?: [],
                    ])
                    ->schema([
                        Select::make('provider')->label('Sağlayıcı')
                            ->options(['basic' => 'Varsayılan (sağlayıcı NS)', 'custom' => 'Özel nameserver'])
                            ->default('basic')->live()->required(),
                        TagsInput::make('hosts')->label('Nameserver listesi')
                            ->placeholder('ns1.ornek.com')
                            ->helperText('En az 2 adet. Yalnızca "Özel" seçilince geçerli.')
                            ->visible(fn ($get): bool => $get('provider') === 'custom'),
                    ])
                    ->action(fn (array $data, DomainName $record) => self::run($record, fn (DomainManagementService $s) => $s->setNameservers($record, (string) $data['provider'], $data['hosts'] ?? []))),

                ActionGroup::make([
                    Action::make('renew')
                        ->label('Süre Uzat / Yenile')
                        ->icon('heroicon-o-calendar-days')
                        ->color('success')
                        ->schema([
                            Select::make('years')->label('Yıl')
                                ->options(array_combine(range(1, 10), array_map(fn ($y) => $y.' yıl', range(1, 10))))
                                ->default(1)->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalDescription('Domain yenileme işlemi sağlayıcı bakiyenizden ücretlendirilir.')
                        ->action(fn (array $data, DomainName $record) => self::run($record, fn (DomainManagementService $s) => $s->renew($record, (int) $data['years']))),

                    Action::make('privacy')
                        ->label(fn (DomainName $record): string => $record->privacy === 'high' ? 'Gizliliği Kapat' : 'Gizliliği Aç')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn (DomainName $record) => self::run($record, fn (DomainManagementService $s) => $s->setPrivacy($record, $record->privacy !== 'high'))),

                    Action::make('autorenew')
                        ->label(fn (DomainName $record): string => $record->auto_renew ? 'Oto. Yenilemeyi Kapat' : 'Oto. Yenilemeyi Aç')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->action(fn (DomainName $record) => self::run($record, fn (DomainManagementService $s) => $s->setAutoRenew($record, ! $record->auto_renew))),

                    Action::make('authcode')
                        ->label('Transfer (Auth) Kodu')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->action(function (DomainName $record): void {
                            try {
                                $result = app(DomainManagementService::class)->authCode($record);
                                Notification::make()
                                    ->title($result['ok'] ? 'Auth kodu' : 'Alınamadı')
                                    ->body($result['ok'] ? ('EPP/Auth kodu: '.($result['code'] ?? '—')) : $result['message'])
                                    ->{$result['ok'] ? 'success' : 'danger'}()
                                    ->persistent()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()->title('Hata')->body($e->getMessage())->danger()->send();
                            }
                        }),

                    Action::make('refresh')
                        ->label('Bilgileri Güncelle')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (DomainName $record): void {
                            try {
                                $ok = app(DomainManagementService::class)->refresh($record);
                                Notification::make()->title($ok ? 'Güncellendi' : 'Bilgi alınamadı')->{$ok ? 'success' : 'warning'}()->send();
                            } catch (\Throwable $e) {
                                Notification::make()->title('Hata')->body($e->getMessage())->danger()->send();
                            }
                        }),
                ])
                    ->label('Diğer')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->button(),
            ]);
    }

    /** @return list<array<string, mixed>> */
    private static function loadDns(DomainName $record): array
    {
        try {
            return array_map(fn (array $r) => [
                'type' => $r['type'],
                'name' => $r['name'],
                'value' => $r['value'],
                'ttl' => $r['ttl'],
                'priority' => $r['priority'],
            ], app(DomainManagementService::class)->dnsRecords($record));
        } catch (\Throwable $e) {
            Notification::make()->title('DNS kayıtları yüklenemedi')->body($e->getMessage())->danger()->send();

            return [];
        }
    }

    /**
     * @param  \Closure(DomainManagementService): array{ok: bool, message: string}  $callback
     */
    private static function run(DomainName $record, \Closure $callback): void
    {
        try {
            $result = $callback(app(DomainManagementService::class));
            Notification::make()
                ->title($result['ok'] ? 'Başarılı' : 'Başarısız')
                ->body($result['message'])
                ->{$result['ok'] ? 'success' : 'danger'}()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Hata')->body($e->getMessage())->danger()->send();
        }
    }
}
