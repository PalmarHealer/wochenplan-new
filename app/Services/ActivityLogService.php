<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity
     */
    public function log(
        string $action,
        string $category,
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?string $resourceLabel = null,
        ?array $content = null,
        ?bool $isSuspicious = false,
        ?string $notes = null,
        ?int $userId = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId ?? Auth::id(),
            'timestamp' => now(),
            'ip_address' => $this->getClientIp(),
            'user_agent' => Request::userAgent(),
            'action' => $action,
            'action_category' => $category,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'resource_label' => $resourceLabel,
            'content' => $content,
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'session_id' => session()->getId(),
            'is_suspicious' => $isSuspicious,
            'notes' => $notes,
        ]);
    }

    /**
     * Log a successful login
     */
    public function logLogin(User $user): ActivityLog
    {
        return $this->log(
            action: ActivityLog::ACTION_LOGIN,
            category: ActivityLog::CATEGORY_AUTH,
            resourceType: User::class,
            resourceId: (string) $user->id,
            resourceLabel: "User: {$user->display_name}",
            userId: $user->id,
        );
    }

    /**
     * Log a failed login attempt
     */
    public function logLoginFailed(string $email): ActivityLog
    {
        $isSuspicious = $this->checkLoginSuspicious($email);

        return $this->log(
            action: ActivityLog::ACTION_LOGIN_FAILED,
            category: ActivityLog::CATEGORY_SECURITY,
            resourceType: 'login_attempt',
            resourceLabel: "Email: {$email}",
            content: ['attempted_email' => $email],
            isSuspicious: $isSuspicious,
        );
    }

    /**
     * Log a logout
     */
    public function logLogout(?User $user = null): ActivityLog
    {
        $user = $user ?? Auth::user();

        return $this->log(
            action: ActivityLog::ACTION_LOGOUT,
            category: ActivityLog::CATEGORY_AUTH,
            resourceType: User::class,
            resourceId: $user ? (string) $user->id : null,
            resourceLabel: $user ? "User: {$user->display_name}" : null,
            userId: $user?->id,
        );
    }

    /**
     * Log a model creation
     */
    public function logCreate(Model $model, ?string $label = null): ActivityLog
    {
        return $this->log(
            action: ActivityLog::ACTION_CREATE,
            category: ActivityLog::CATEGORY_DATA,
            resourceType: get_class($model),
            resourceId: (string) $model->getKey(),
            resourceLabel: $label ?? $this->getModelLabel($model),
            content: ['after' => $model->toArray()],
        );
    }

    /**
     * Log a model update
     */
    public function logUpdate(Model $model, array $originalData, ?string $label = null): ActivityLog
    {
        $changes = [];
        $currentData = $model->toArray();

        foreach ($model->getDirty() as $key => $value) {
            $changes[$key] = [
                'before' => $originalData[$key] ?? null,
                'after' => $value,
            ];
        }

        return $this->log(
            action: ActivityLog::ACTION_UPDATE,
            category: ActivityLog::CATEGORY_DATA,
            resourceType: get_class($model),
            resourceId: (string) $model->getKey(),
            resourceLabel: $label ?? $this->getModelLabel($model),
            content: [
                'before' => $originalData,
                'after' => $currentData,
                'changes' => $changes,
            ],
        );
    }

    /**
     * Log a model deletion
     */
    public function logDelete(Model $model, ?string $label = null): ActivityLog
    {
        return $this->log(
            action: ActivityLog::ACTION_DELETE,
            category: ActivityLog::CATEGORY_DATA,
            resourceType: get_class($model),
            resourceId: (string) $model->getKey(),
            resourceLabel: $label ?? $this->getModelLabel($model),
            content: ['before' => $model->toArray()],
        );
    }

    /**
     * Log a page visit
     */
    public function logVisit(?string $pageName = null): ActivityLog
    {
        return $this->log(
            action: ActivityLog::ACTION_VISIT,
            category: ActivityLog::CATEGORY_NAVIGATION,
            resourceType: 'page',
            resourceLabel: $pageName ?? Request::path(),
        );
    }

    /**
     * Log a data view
     */
    public function logView(Model $model, ?string $label = null): ActivityLog
    {
        return $this->log(
            action: ActivityLog::ACTION_VIEW,
            category: ActivityLog::CATEGORY_NAVIGATION,
            resourceType: get_class($model),
            resourceId: (string) $model->getKey(),
            resourceLabel: $label ?? $this->getModelLabel($model),
        );
    }

    /**
     * Log a user click/interaction
     */
    public function logClick(string $element, ?array $context = null): ActivityLog
    {
        return $this->log(
            action: ActivityLog::ACTION_CLICK,
            category: ActivityLog::CATEGORY_INTERACTION,
            resourceType: 'ui_element',
            resourceLabel: $element,
            content: $context,
        );
    }

    /**
     * Log an export action
     */
    public function logExport(string $type, ?string $label = null, ?array $context = null): ActivityLog
    {
        return $this->log(
            action: ActivityLog::ACTION_EXPORT,
            category: ActivityLog::CATEGORY_DATA,
            resourceType: $type,
            resourceLabel: $label ?? $type,
            content: $context,
        );
    }

    /**
     * Log an import action
     */
    public function logImport(string $type, ?string $label = null, ?array $context = null): ActivityLog
    {
        return $this->log(
            action: ActivityLog::ACTION_IMPORT,
            category: ActivityLog::CATEGORY_DATA,
            resourceType: $type,
            resourceLabel: $label ?? $type,
            content: $context,
        );
    }

    /**
     * Log a download action
     */
    public function logDownload(string $type, ?string $resourceId = null, ?string $label = null): ActivityLog
    {
        return $this->log(
            action: ActivityLog::ACTION_DOWNLOAD,
            category: ActivityLog::CATEGORY_DATA,
            resourceType: $type,
            resourceId: $resourceId,
            resourceLabel: $label,
        );
    }

    /**
     * Log a suspicious activity
     */
    public function logSuspicious(string $action, string $reason, ?array $context = null): ActivityLog
    {
        return $this->log(
            action: $action,
            category: ActivityLog::CATEGORY_SECURITY,
            content: array_merge($context ?? [], ['reason' => $reason]),
            isSuspicious: true,
            notes: $reason,
        );
    }

    /**
     * Get the client IP address, considering proxies
     */
    protected function getClientIp(): ?string
    {
        $trustedProxies = config('trustedproxy.proxies', []);

        // Check X-Forwarded-For if behind trusted proxy
        if (Request::hasHeader('X-Forwarded-For')) {
            $forwardedFor = Request::header('X-Forwarded-For');
            $ips = array_map('trim', explode(',', $forwardedFor));

            // Return the first non-proxy IP
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return Request::ip();
    }

    /**
     * Try to get a human-readable label for a model
     */
    protected function getModelLabel(Model $model): string
    {
        $className = class_basename($model);

        // Try common label fields
        foreach (['name', 'title', 'display_name', 'label', 'email'] as $field) {
            if (isset($model->{$field})) {
                return "{$className}: {$model->{$field}}";
            }
        }

        return "{$className} #{$model->getKey()}";
    }

    /**
     * Check if login attempt is suspicious (too many failed attempts)
     */
    protected function checkLoginSuspicious(string $email): bool
    {
        $recentFailedAttempts = ActivityLog::where('action', ActivityLog::ACTION_LOGIN_FAILED)
            ->where('ip_address', $this->getClientIp())
            ->where('timestamp', '>=', now()->subMinutes(15))
            ->count();

        // More than 5 failed attempts in 15 minutes is suspicious
        return $recentFailedAttempts >= 5;
    }
}
