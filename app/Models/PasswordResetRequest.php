<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetRequest extends Model
{



    protected $table = 'password_reset_request';

    protected $fillable = [
        'request', 'process',
    ];

 function user()
 {
     return $this->belongsTo(User::class)->withDefault([
         'id' => 0
     ]);
 }
}
