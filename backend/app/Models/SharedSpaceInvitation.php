<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedSpaceInvitation extends Model
{
    protected $fillable = ['shared_space_id', 'invited_by', 'email', 'token', 'status'];

    public function space(): BelongsTo    { return $this->belongsTo(SharedSpace::class, 'shared_space_id'); }
    public function inviter(): BelongsTo  { return $this->belongsTo(User::class, 'invited_by'); }
}
