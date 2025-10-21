<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DayPdfResource\Pages;
use App\Filament\Resources\DayPdfResource\RelationManagers;
use App\Models\DayPdf;
use App\Services\PdfExportService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Response;
use ZipArchive;

class DayPdfResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = DayPdf::class;

    protected static ?string $navigationIcon = 'tabler-file-type-pdf';

    protected static ?string $navigationLabel = "PDF-Exporte";

    protected static ?int $navigationSort = 10;

    protected static ?string $label = 'PDF-Export';

    public static function getPluralLabel(): string
    {
        return 'PDF-Exporte';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Datum')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->format('Y-m-d')
                    ->required(),
                Forms\Components\Placeholder::make('info')
                    ->label('Hinweis')
                    ->content('Das PDF wird automatisch beim Speichern generiert.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_outdated')
                    ->label('Veraltet')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Geändert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_outdated')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur veraltete')
                    ->falseLabel('Nur aktuelle')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Herunterladen')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (DayPdf $record) {
                        $pdfService = app(PdfExportService::class);
                        $base64Content = $pdfService->getOrGeneratePdf($record->date);
                        $binaryContent = base64_decode($base64Content);

                        return Response::streamDownload(function () use ($binaryContent) {
                            echo $binaryContent;
                        }, 'tagesplan-' . $record->date . '.pdf', ['Content-Type' => 'application/pdf']);
                    }),
                Tables\Actions\Action::make('regenerate')
                    ->label('Neu generieren')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (DayPdf $record) {
                        $record->is_outdated = true;
                        $record->save();

                        $pdfService = app(PdfExportService::class);
                        $pdfService->getOrGeneratePdf($record->date);

                        Notification::make()
                            ->title('PDF wurde neu generiert')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('download_zip')
                        ->label('Als ZIP herunterladen')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->action(function (Collection $records) {
                            $pdfService = app(PdfExportService::class);
                            $zipFileName = 'tagesplaene-' . now()->format('Y-m-d-His') . '.zip';
                            $zipPath = storage_path('app/temp/' . $zipFileName);

                            // Create temp directory if it doesn't exist
                            if (!file_exists(storage_path('app/temp'))) {
                                mkdir(storage_path('app/temp'), 0755, true);
                            }

                            $zip = new ZipArchive();
                            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                                foreach ($records as $record) {
                                    $base64Content = $pdfService->getOrGeneratePdf($record->date);
                                    $binaryContent = base64_decode($base64Content);
                                    $zip->addFromString('tagesplan-' . $record->date . '.pdf', $binaryContent);
                                }
                                $zip->close();

                                return Response::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
                            }

                            Notification::make()
                                ->title('Fehler beim Erstellen der ZIP-Datei')
                                ->danger()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDayPdfs::route('/'),
            'create' => Pages\CreateDayPdf::route('/create'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'delete',
            'delete_any',
        ];
    }
}
