<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'tabler-activity';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $slug = 'activity';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Activity Details')
                    ->schema([
                        Forms\Components\TextInput::make('action')
                            ->label('Aktion')
                            ->disabled(),
                        Forms\Components\TextInput::make('action_category')
                            ->label('Kategorie')
                            ->disabled(),
                        Forms\Components\TextInput::make('user.display_name')
                            ->label('Benutzer')
                            ->placeholder('System')
                            ->disabled(),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP-Adresse')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('timestamp')
                            ->label('Zeitstempel')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Resource')
                    ->schema([
                        Forms\Components\TextInput::make('resource_type')
                            ->label('Ressourcentyp')
                            ->disabled(),
                        Forms\Components\TextInput::make('resource_id')
                            ->label('Ressourcen-ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('resource_label')
                            ->label('Ressourcen-Label')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => preg_replace('/^[^:]+:\s*/', '', $state ?? '')),
                    ])->columns(3),

                Forms\Components\Section::make('Request')
                    ->schema([
                        Forms\Components\TextInput::make('url')
                            ->label('URL')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('method')
                            ->label('HTTP-Methode')
                            ->disabled(),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('User-Agent')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('Inhalt')
                            ->disabled()
                            ->columnSpanFull()
                            ->rows(10)
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ''),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed()
                    ->visible(fn ($record) => ! empty($record?->content)),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\Toggle::make('is_suspicious')
                            ->label('Verdächtig')
                            ->disabled(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record?->is_suspicious || $record?->notes),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('timestamp')
                    ->label('Zeit')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Benutzer')
                    ->sortable()
                    ->searchable()
                    ->placeholder('System')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('action')
                    ->label('Aktion')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'success',
                        'logout' => 'gray',
                        'login_failed' => 'danger',
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        'view', 'visit' => 'info',
                        'export', 'download' => 'primary',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('action_category')
                    ->label('Kategorie')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'auth' => 'primary',
                        'data' => 'success',
                        'navigation' => 'info',
                        'interaction' => 'gray',
                        'security' => 'danger',
                        'system' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('resource_label')
                    ->label('Ressource')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => preg_replace('/^[^:]+:\s*/', '', $state ?? '')),

                Tables\Columns\TextColumn::make('resource_type')
                    ->label('Ressourcentyp')
                    ->searchable()
                    ->limit(40)
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('method')
                    ->label('HTTP-Methode')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'GET' => 'info',
                        'POST' => 'success',
                        'PUT', 'PATCH' => 'warning',
                        'DELETE' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User-Agent')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_suspicious')
                    ->label('Verdächtig')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('timestamp', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Aktion')
                    ->options([
                        ActivityLog::ACTION_LOGIN => 'Login',
                        ActivityLog::ACTION_LOGIN_FAILED => 'Login fehlgeschlagen',
                        ActivityLog::ACTION_LOGOUT => 'Logout',
                        ActivityLog::ACTION_CREATE => 'Erstellen',
                        ActivityLog::ACTION_UPDATE => 'Aktualisieren',
                        ActivityLog::ACTION_DELETE => 'Löschen',
                        ActivityLog::ACTION_VIEW => 'Ansehen',
                        ActivityLog::ACTION_VISIT => 'Besuch',
                        ActivityLog::ACTION_EXPORT => 'Export',
                        ActivityLog::ACTION_IMPORT => 'Import',
                        ActivityLog::ACTION_DOWNLOAD => 'Download',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('action_category')
                    ->label('Kategorie')
                    ->options([
                        ActivityLog::CATEGORY_AUTH => 'Authentifizierung',
                        ActivityLog::CATEGORY_DATA => 'Daten',
                        ActivityLog::CATEGORY_NAVIGATION => 'Navigation',
                        ActivityLog::CATEGORY_INTERACTION => 'Interaktion',
                        ActivityLog::CATEGORY_SECURITY => 'Sicherheit',
                        ActivityLog::CATEGORY_SYSTEM => 'System',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Benutzer')
                    ->relationship('user', 'display_name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Tables\Filters\Filter::make('is_suspicious')
                    ->label('Nur verdächtige')
                    ->query(fn (Builder $query): Builder => $query->where('is_suspicious', true))
                    ->toggle(),

                Tables\Filters\Filter::make('timestamp')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Von')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Bis')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('timestamp', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('timestamp', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\ActivityLogExporter::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => 'Activity Log Details')
                    ->modalWidth('5xl'),
            ])
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Logs are created automatically
    }

    public static function canEdit($record): bool
    {
        return false; // Logs should be immutable
    }

    public static function canDelete($record): bool
    {
        return false; // Logs should be immutable
    }
}
