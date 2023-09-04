<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Resources\ProductResource\RelationManagers\TagsRelationManager;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static array $statuses =  [
        'in stock' => 'in stock',
        'sold out' => 'sold out',
        'coming soon' => 'coming soon',
    ];

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('price')
                    ->required()
                    ->rule('numeric'),

                Radio::make('status')
                    ->options(self::$statuses),

                Select::make('category_id')
                    ->relationship('category', 'name'),

                // Select::make('tags')
                // ->relationship('tags', 'name')
                // ->multiple(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('price')
                    ->sortable()
                    ->money('usd')
                    ->getStateUsing(function (Product $record): float {
                        return $record->price / 100;
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'in stock' => 'success',
                            'sold out' => 'danger',
                            'coming soon' => 'info',
                        };
                    }),

                TextColumn::make('category.name'),

                TextColumn::make('tags.name')
                    ->badge(),
            ])

            ->defaultSort('name', 'asc')

            ->filters(
                [
                    Tables\Filters\SelectFilter::make('status')
                        ->options(self::$statuses),
                    Tables\Filters\SelectFilter::make('category')
                        ->relationship('category', 'name'),
                    Tables\Filters\Filter::make('created_from')
                        ->form([
                            Forms\Components\DatePicker::make('created_from'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['created_from'],
                                    function (Builder $query, $date): Builder {
                                        return $query->whereDate('created_at', '>=', $date);
                                    }
                                );
                        }),
                    Tables\Filters\Filter::make('created_until')
                        ->form([
                            Forms\Components\DatePicker::make('created_until'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['created_until'],
                                    function (Builder $query, $date): Builder {
                                        return $query->whereDate('created_at', '<=', $date);
                                    }
                                );
                        }),

                ],
                layout: FiltersLayout::AboveContent
            )
            ->filtersFormColumns(4)

            ->actions([
                Tables\Actions\EditAction::make(),
                DeleteAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])

            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
