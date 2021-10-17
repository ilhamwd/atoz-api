<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOrder extends Model {

    protected $table = "user_order";
    protected $primaryKey = "order_no";
    protected $keyType = "string";

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_uuid');
    }
}