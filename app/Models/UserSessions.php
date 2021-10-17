<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSessions extends Model {

    protected $table = "user_sessions";
    protected $primaryKey = "token";
    protected $keyType = "string";
    
    public $timestamps = false;

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_uuid');
    }
}