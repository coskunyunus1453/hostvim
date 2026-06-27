<?php

namespace App\Filament\Resources\OwnershipTransfers;

use App\Filament\Resources\OwnershipTransfers\Pages\EditOwnershipTransfer;
use App\Filament\Resources\OwnershipTransfers\Pages\ListOwnershipTransfers;
use App\Filament\Resources\OwnershipTransfers\Schemas\OwnershipTransferForm;
use App\Filament\Resources\OwnershipTransfers\Tables\OwnershipTransfersTable;
use App\Models\OwnershipTransferRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class OwnershipTransferResource extends Resource
{
    protected static ?string $model = OwnershipTransferRequest::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Müşteriler & Siparişler';

    protected static ?string $navigationLabel = 'Devir Talepleri';

    protected static ?string $modelLabel = 'Devir talebi';

    protected static ?string $pluralModelLabel = 'Devir talepleri';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = OwnershipTransferRequest::query()
            ->where('status', OwnershipTransferRequest::STATUS_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return OwnershipTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OwnershipTransfersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOwnershipTransfers::route('/'),
            'edit' => EditOwnershipTransfer::route('/{record}/edit'),
        ];
    }
}
