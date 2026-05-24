<?php

namespace App\Filament\Resources\OrderResource\Concerns;

use App\Http\Controllers\Admin\OrderInvoiceController;
use App\Models\Order;
use Filament\Actions;

trait HasInvoiceActions
{
    /**
     * @return array<int, Actions\Action>
     */
    protected static function getInvoiceHeaderActions(): array
    {
        return [
            Actions\Action::make('print_invoice')
                ->label(__('ecommerce.print_invoice'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (Order $record): string => OrderInvoiceController::printUrl($record))
                ->openUrlInNewTab(),
            Actions\Action::make('download_invoice')
                ->label(__('ecommerce.download_invoice'))
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (Order $record): string => OrderInvoiceController::downloadUrl($record))
                ->openUrlInNewTab(),
        ];
    }
}
