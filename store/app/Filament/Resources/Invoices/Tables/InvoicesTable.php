<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Services\Invoice\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Fatura No')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('order.order_number')
                    ->label('Sipariş')
                    ->searchable()
                    ->placeholder('—')
                    ->url(fn (Invoice $r): ?string => $r->order_id
                        ? \App\Filament\Resources\Orders\OrderResource::getUrl('edit', ['record' => $r->order_id])
                        : null),
                TextColumn::make('customer_name')
                    ->label('Müşteri')
                    ->searchable()
                    ->description(fn (Invoice $r): ?string => $r->customer_email)
                    ->wrap(),
                TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Invoice::typeLabel($state))
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Invoice::statusLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        Invoice::STATUS_DRAFT => 'warning',
                        Invoice::STATUS_QUEUED => 'info',
                        Invoice::STATUS_ISSUED, Invoice::STATUS_SENT => 'info',
                        Invoice::STATUS_ACCEPTED => 'success',
                        Invoice::STATUS_REJECTED, Invoice::STATUS_ERROR => 'danger',
                        Invoice::STATUS_CANCELLED => 'gray',
                        default => 'gray',
                    })
                    ->tooltip(fn (Invoice $r): ?string => $r->error_message),
                TextColumn::make('total')
                    ->label('Tutar')
                    ->money('TRY')
                    ->weight('bold'),
                TextColumn::make('issued_at')
                    ->label('Kesim')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Oluşturma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        Invoice::STATUS_DRAFT => 'Taslak (Proforma)',
                        Invoice::STATUS_ISSUED => 'E-Fatura kesildi',
                        Invoice::STATUS_SENT => 'Gönderildi',
                        Invoice::STATUS_ACCEPTED => 'Kabul edildi',
                        Invoice::STATUS_REJECTED => 'Reddedildi',
                        Invoice::STATUS_ERROR => 'Hata',
                        Invoice::STATUS_CANCELLED => 'İptal',
                    ]),
                SelectFilter::make('type')
                    ->label('Tür')
                    ->options([
                        Invoice::TYPE_EINVOICE => 'e-Fatura',
                        Invoice::TYPE_EARCHIVE => 'e-Arşiv',
                    ]),
            ])
            ->recordActions([
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $r): string => route('admin.invoices.pdf', $r))
                    ->openUrlInNewTab(),
                ActionGroup::make([
                    Action::make('issue')
                        ->label('E-Fatura kes')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn (Invoice $r): bool => ! $r->isIssued() && $r->status !== Invoice::STATUS_CANCELLED)
                        ->requiresConfirmation()
                        ->modalHeading('Resmi e-Fatura/e-Arşiv kes')
                        ->modalDescription('Fatura seçili entegratöre gönderilecek ve kontör tüketilecek. Devam edilsin mi?')
                        ->action(function (Invoice $record, InvoiceService $service): void {
                            $result = $service->issue($record);
                            if ($result->isIssued()) {
                                Notification::make()->title('E-Fatura kesildi')->success()->send();
                            } else {
                                Notification::make()
                                    ->title('E-Fatura kesilemedi')
                                    ->body($result->error_message ?: 'Bilinmeyen hata')
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('refreshStatus')
                        ->label('Durum sorgula')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn (Invoice $r): bool => $r->isIssued())
                        ->action(function (Invoice $record, InvoiceService $service): void {
                            $service->refreshStatus($record);
                            Notification::make()->title('Durum güncellendi')->success()->send();
                        }),
                    Action::make('regenerateDraft')
                        ->label('Taslağı yeniden oluştur')
                        ->icon('heroicon-o-document-arrow-up')
                        ->visible(fn (Invoice $r): bool => $r->isDraft())
                        ->action(function (Invoice $record, InvoiceService $service): void {
                            $service->renderPdf($record);
                            Notification::make()->title('Taslak PDF yenilendi')->success()->send();
                        }),
                ])->label('İşlemler')->icon('heroicon-m-ellipsis-vertical')->button(),
            ]);
    }
}
