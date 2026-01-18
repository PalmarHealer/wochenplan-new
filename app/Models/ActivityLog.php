<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'timestamp',
        'ip_address',
        'user_agent',
        'action',
        'action_category',
        'resource_type',
        'resource_id',
        'resource_label',
        'content',
        'url',
        'method',
        'response_code',
        'session_id',
        'is_suspicious',
        'notes',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'content' => 'array',
        'is_suspicious' => 'boolean',
    ];

    // Action categories
    public const CATEGORY_AUTH = 'auth';
    public const CATEGORY_DATA = 'data';
    public const CATEGORY_NAVIGATION = 'navigation';
    public const CATEGORY_INTERACTION = 'interaction';
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_SECURITY = 'security';

    // Common actions
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_VIEW = 'view';
    public const ACTION_VISIT = 'visit';
    public const ACTION_CLICK = 'click';
    public const ACTION_EXPORT = 'export';
    public const ACTION_IMPORT = 'import';
    public const ACTION_DOWNLOAD = 'download';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the before/after data for updates
     */
    public function getBeforeAttribute(): ?array
    {
        return $this->content['before'] ?? null;
    }

    /**
     * Get the after data for updates
     */
    public function getAfterAttribute(): ?array
    {
        return $this->content['after'] ?? null;
    }

    /**
     * Get the changes between before and after
     */
    public function getChangesAttribute(): ?array
    {
        return $this->content['changes'] ?? null;
    }

    /**
     * Scope for suspicious activities
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for a specific category
     */
    public function scopeForCategory($query, string $category)
    {
        return $query->where('action_category', $category);
    }

    /**
     * Scope for a specific resource
     */
    public function scopeForResource($query, string $type, ?string $id = null)
    {
        $query->where('resource_type', $type);
        if ($id !== null) {
            $query->where('resource_id', $id);
        }

        return $query;
    }

    /**
     * Scope for a time range
     */
    public function scopeInTimeRange($query, $from, $to = null)
    {
        $query->where('timestamp', '>=', $from);
        if ($to !== null) {
            $query->where('timestamp', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope for IP address
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Get a human-readable description of the activity
     */
    public function getDescriptionAttribute(): string
    {
        $user = $this->user?->display_name ?? $this->user?->name ?? 'Unbekannt';
        $resource = $this->resource_label ?? $this->resource_type ?? '';

        return match ($this->action) {
            self::ACTION_LOGIN => "Benutzer {$user} hat sich angemeldet",
            self::ACTION_LOGIN_FAILED => "Fehlgeschlagener Login-Versuch für {$resource}",
            self::ACTION_LOGOUT => "Benutzer {$user} hat sich abgemeldet",
            self::ACTION_CREATE => "Benutzer {$user} hat {$resource} erstellt",
            self::ACTION_UPDATE => "Benutzer {$user} hat {$resource} aktualisiert",
            self::ACTION_DELETE => "Benutzer {$user} hat {$resource} gelöscht",
            self::ACTION_VIEW => "Benutzer {$user} hat {$resource} angesehen",
            self::ACTION_VISIT => "Benutzer {$user} hat {$this->url} besucht",
            self::ACTION_EXPORT => "Benutzer {$user} hat {$resource} exportiert",
            self::ACTION_IMPORT => "Benutzer {$user} hat {$resource} importiert",
            self::ACTION_DOWNLOAD => "Benutzer {$user} hat {$resource} heruntergeladen",
            default => "Benutzer {$user}: {$this->action} - {$resource}",
        };
    }
}
