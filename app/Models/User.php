<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model {

    protected $table = "user";
    protected $primaryKey = "user_uuid";
    protected $keyType = "string";
    
    public function orders(): HasMany {
        return $this->hasMany(UserOrder::class, 'user_uuid');
    }
}