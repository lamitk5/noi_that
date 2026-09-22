<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'title',
        'status',
        'metadata',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('id', 'asc');
    }

    public function latestMessages(int $limit = 12): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->latest('id')->limit($limit);
    }

    public function isOwnedBy(?User $user, ?string $sessionId): bool
    {
        // If conversation belongs to an authenticated user, strictly require matching user_id
        if ($this->user_id !== null) {
            return $user !== null && (int) $this->user_id === (int) $user->id;
        }

        // Guest conversation (user_id is null) is identified by its unguessable UUID
        return true;
    }
}
