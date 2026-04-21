<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedSpace extends Model
{
    protected $fillable = ['created_by', 'name', 'icon', 'currency'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shared_space_members', 'shared_space_id', 'user_id')
            ->withPivot('role', 'joined_at');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SharedTransaction::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(SharedGoal::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(SharedSpaceInvitation::class);
    }

    public function isMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function isAdmin(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->wherePivot('role', 'admin')->exists();
    }
}
