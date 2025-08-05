<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public ?TemporaryUploadedFile $csvFile = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Importieren')
                ->label('Importieren')
                ->modalHeading('Benutzer per CSV importieren')
                ->modalWidth('md')
                ->action(function (array $data) {
                    $filePath = $data['csvFile'];

                    if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'csv') {
                        Notification::make()
                            ->title('Nur CSV-Dateien sind erlaubt.')
                            ->danger()
                            ->send();
                        Storage::disk('public')->delete($filePath);
                        return;
                    }

                    $fullPath = Storage::disk('public')->path($filePath);
                    if ($fullPath === false) {
                        Notification::make()
                            ->title('Die Datei konnte nicht geöffnet werden.')
                            ->danger()
                            ->send();
                        Storage::disk('public')->delete($filePath);
                        return;
                    }

                    try {
                        $handle = fopen($fullPath, 'r');
                        if ($handle === false) {
                            Notification::make()
                                ->title('Die Datei konnte nicht geöffnet werden.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $header = fgetcsv($handle, 0, ';');
                        if ($header === false || count($header) < 3) {
                            Notification::make()
                                ->title('Ungültiges CSV-Format. Erwartet: Vorname;Nachname;Email')
                                ->danger()
                                ->send();
                            fclose($handle);
                            return;
                        }

                        $count = 0;
                        while (($row = fgetcsv($handle, 0, ';')) !== false) {
                            if (count($row) < 3) continue;
                            [$firstName, $lastName, $email] = $row;
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

                            $user = User::firstOrNew(['email' => $email]);
                            $user->display_name = $firstName;
                            $user->name = $firstName . ' ' . $lastName;
                            if (!$user->exists) {
                                $user->password = Hash::make(Str::random(32));
                                $user->save();
                                $count++;
                            }
                        }

                        Notification::make()
                            ->title($count . ' Benutzer erfolgreich importiert.')
                            ->success()
                            ->send();
                    } finally {
                        fclose($handle);
                        Storage::disk('public')->delete($filePath);
                    }
                })
                ->form([
                    FileUpload::make('csvFile')
                        ->label(false)
                        ->required()
                        ->directory('imports'),

                ]),

            Actions\CreateAction::make(),
        ];
    }
}
