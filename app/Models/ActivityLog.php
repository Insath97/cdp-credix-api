<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'payload',
        'level',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    /**
     * created_at is deliberately NOT hidden.
     *
     * This is an audit trail: when something happened is the one field nobody
     * can do without, and hiding it -- the habit that suits lookup tables --
     * left the Activity Logs screen printing a dash in its Date column for
     * every row ever recorded.
     */
    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * Get the user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
