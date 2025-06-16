## Wochenplan 2.0

### TODO:

- Dashboard
---
- Features
  - Pre fillable lesson resource (see end of file)
---
- Policies
  - LessonTemplate
    - Policy
    - Use templates
 
---

- Plugins
  - Socialite

---

### Features for later

- Plugins
  - Laravel PDF
- Policies
    - LayoutOverride
- Resources
    - LayoutOverride


```PHP

use App\Models\LessonTemplate;

public static function form(Form $form): Form
{
$request = request();
$defaults = [];

    // Prüfen, ob der 'copy' Parameter vorhanden ist
    if ($request->has('copy')) {
        // Template laden
        $template = LessonTemplate::with('assignedUsers')->find($request->input('copy'));

        if ($template) {
            // Werte aus dem Template übernehmen
            $defaults = [
                'name' => $template->name,
                'description' => $template->description,
                'notes' => $template->notes,
                'disabled' => $template->disabled,
                'date' => $template->date,
                'color' => $template->color,
                'room' => $template->room,
                'lesson_time' => $template->lesson_time,
                'assignedUsers' => $template->assignedUsers->pluck('id')->toArray(),
            ];
        }
    }

    return $form->schema([
        Forms\Components\TextInput::make('name')
            ->default($defaults['name'] ?? ''),
        Forms\Components\TextInput::make('description')
            ->default($defaults['description'] ?? ''),
        // ... die anderen Felder analog ...
        Forms\Components\Select::make('assignedUsers')
            ->multiple()
            ->default($defaults['assignedUsers'] ?? []),
        // usw.
    ]);
}

```
```PHP
   use Illuminate\Http\Request;

   public static function form(Form $form): Form
   {
       $request = request();

       return $form->schema([
           Forms\Components\TextInput::make('name')
               ->default($request->input('name')),
           Forms\Components\DatePicker::make('date')
               ->default($request->input('date', now())),
           // ... usw.
       ]);
   }
```
