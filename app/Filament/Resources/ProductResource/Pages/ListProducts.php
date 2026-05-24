<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Exports\ProductsImportTemplateExport;
use App\Filament\Pages\ImportProductsPage;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_products')
                ->label(__('ecommerce.import_products'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->url(ImportProductsPage::getUrl()),
            Actions\Action::make('download_template')
                ->label(__('ecommerce.download_import_template'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new ProductsImportTemplateExport,
                    'products-import-template.xlsx'
                )),
            Actions\CreateAction::make(),
        ];
    }
}
