<?php

namespace App\Filament\Pages;

use App\Models\ChatConversation;
use App\Services\AiChat\ChatService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

class AiChat extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'tabler-sparkles';

    protected static ?string $navigationLabel = 'Assistant';

    protected static ?string $slug = 'assistant';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.ai-chat';

    protected ?string $subheading = 'Dies ist ein KI-Assistent. Antworten können fehlerhaft sein — bitte mit Vorsicht verwenden. Die KI läuft auf privaten Servern.';

    #[Url(as: 'chat')]
    public ?int $conversationId = null;

    public string $newMessage = '';

    public array $messages = [];

    public function mount(): void
    {
        if ($this->conversationId) {
            $this->loadMessages();
        }
    }

    public function getTitle(): string
    {
        return 'Assistant';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ChatConversation::where('user_id', auth()->id())->latest('updated_at')
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Chat')
                    ->default('Neuer Chat')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('messages_count')
                    ->label('Nachrichten')
                    ->counts('messages')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Zuletzt aktiv')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Action::make('open')
                    ->label('Öffnen')
                    ->icon('tabler-message')
                    ->url(fn (ChatConversation $record) => static::getUrl(['chat' => $record->id])),
                Action::make('rename')
                    ->label('Umbenennen')
                    ->icon('tabler-pencil')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(100),
                    ])
                    ->fillForm(fn (ChatConversation $record) => ['title' => $record->title])
                    ->action(fn (ChatConversation $record, array $data) => $record->update(['title' => $data['title']])),
                DeleteAction::make(),
            ])
            ->recordUrl(fn (ChatConversation $record) => static::getUrl(['chat' => $record->id]))
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('Noch keine Chats')
            ->emptyStateDescription('Starte einen Chat mit dem Textfeld oben.')
            ->emptyStateIcon('tabler-message-chatbot')
            ->paginated([10, 25, 50]);
    }

    /**
     * Start a new chat from the input widget on the list page.
     */
    public function startChat(): void
    {
        $text = trim($this->newMessage);
        if ($text === '') {
            return;
        }

        $conversation = ChatConversation::create(['user_id' => auth()->id()]);
        $conversation->messages()->create(['role' => 'user', 'content' => $text]);

        $this->newMessage = '';
        $this->conversationId = $conversation->id;
        $this->pendingStream = true;
        $this->loadMessages();
    }

    public bool $pendingStream = false;

    /**
     * Send a message within an open conversation.
     */
    public function saveUserMessage(string $text): ?int
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        if (! $this->conversationId) {
            $conversation = ChatConversation::create(['user_id' => auth()->id()]);
            $this->conversationId = $conversation->id;
        }

        $conversation = ChatConversation::where('id', $this->conversationId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $conversation) {
            return null;
        }

        $conversation->messages()->create(['role' => 'user', 'content' => $text]);

        $this->messages[] = [
            'id' => 0, 'role' => 'user', 'content' => $text,
            'tool_name' => null, 'tool_calls' => null,
            'pending_action' => null, 'action_status' => null,
            'thinking' => null, 'created_at' => now()->format('H:i'),
        ];

        return $this->conversationId;
    }

    public function refreshMessages(): void
    {
        if ($this->conversationId) {
            $this->loadMessages();
        }
    }

    public function backToList(): void
    {
        $this->conversationId = null;
        $this->messages = [];
    }

    public function approveAction(int $messageId): void
    {
        $this->messages = app(ChatService::class)->approveAction($messageId, auth()->user());
    }

    public function rejectAction(int $messageId): void
    {
        $this->messages = app(ChatService::class)->rejectAction($messageId, auth()->user());
    }

    private function loadMessages(): void
    {
        $conversation = ChatConversation::where('id', $this->conversationId)
            ->where('user_id', auth()->id())
            ->first();

        if ($conversation) {
            $this->messages = app(ChatService::class)->getConversationMessages($conversation);
        }
    }
}
