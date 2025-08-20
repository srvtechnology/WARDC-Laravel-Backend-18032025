<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditModel extends Model
{
    use HasFactory;
    
    protected $table = 'activity_log';

      public function userData() {
            return $this->hasOne('App\Models\User', 'id', 'causer_id');
        }

 
}
