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

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 7;

    protected static ?string $label = 'PDF-Export';

    public static function getPluralLabel(): string
    {
        return 'PDF-Exporte';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\DatePicker::make('date')
                        ->label('Datum')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->format('Y-m-d')
                        ->default(date('Y-m-d'))
                        ->disabledDates(function () {
                            $disabledDates = [];
                            $start = now()->startOfYear();
                            $end = now()->addYear()->endOfYear();

                            while ($start <= $end) {
                                if ($start->isWeekend()) {
                                    $disabledDates[] = $start->format('Y-m-d');
                                }
                                $start->addDay();
                            }

                            return $disabledDates;
                        })
                        ->required(),
                ])
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
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_outdated')
                    ->label('Aktuell')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable(),
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
                    ->label('')
                    ->icon('tabler-download')
                    ->iconButton()
                    ->action(function (DayPdf $record) {
                        $pdfService = app(PdfExportService::class);
                        $base64Content = $pdfService->getOrGeneratePdf($record->date);
                        $binaryContent = base64_decode($base64Content);

                        $filename = $record->date->locale(config('app.locale'))->translatedFormat('l, d.m.Y') . '.pdf';

                        return Response::streamDownload(function () use ($binaryContent) {
                            echo $binaryContent;
                        }, $filename, ['Content-Type' => 'application/pdf']);
                    }),
            ])
            ->recordAction('download')
            ->recordUrl(null)
            ->bulkActions([
                Tables\Actions\BulkAction::make('download')
                    ->label('Herunterladen')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        $pdfService = app(PdfExportService::class);

                        // If only one record, download as PDF directly
                        if ($records->count() === 1) {
                            $record = $records->first();
                            $base64Content = $pdfService->getOrGeneratePdf($record->date);
                            $binaryContent = base64_decode($base64Content);

                            $filename = $record->date->locale(config('app.locale'))->translatedFormat('l, d.m.Y') . '.pdf';

                            return Response::streamDownload(function () use ($binaryContent) {
                                echo $binaryContent;
                            }, $filename, ['Content-Type' => 'application/pdf']);
                        }

                        // Multiple records - create ZIP
                        $sortedRecords = $records->sortBy('date');
                        $firstDate = $sortedRecords->first()->date->format('d.m.Y');
                        $lastDate = $sortedRecords->last()->date->format('d.m.Y');

                        $zipFileName = 'Tagespläne ' . $firstDate . '-' . $lastDate . '.zip';

                        $zipPath = storage_path('app/temp/' . $zipFileName);

                        if (!file_exists(storage_path('app/temp'))) {
                            mkdir(storage_path('app/temp'), 0755, true);
                        }

                        $zip = new ZipArchive();
                        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                            foreach ($sortedRecords as $record) {
                                $base64Content = $pdfService->getOrGeneratePdf($record->date);
                                $binaryContent = base64_decode($base64Content);

                                $filename = $record->date->locale(config('app.locale'))->translatedFormat('l, d.m.Y') . '.pdf';

                                $zip->addFromString($filename, $binaryContent);
                            }
                            $zip->close();

                            return Response::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
                        }

                        Notification::make()
                            ->title('Fehler beim Erstellen der ZIP-Datei')
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('regenerate')
                    ->label('Neu generieren')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('PDFs neu generieren')
                    ->modalDescription('Möchten Sie die ausgewählten PDFs wirklich neu generieren?')
                    ->action(function (Collection $records) {
                        $pdfService = app(PdfExportService::class);

                        foreach ($records as $record) {
                            $record->is_outdated = true;
                            $record->save();
                            $pdfService->getOrGeneratePdf($record->date);
                        }

                        Notification::make()
                            ->title(count($records) . ' PDF(s) wurden neu generiert')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
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
