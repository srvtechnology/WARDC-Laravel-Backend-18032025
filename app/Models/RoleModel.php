<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    protected $table = "roles";
     protected $fillable = ['name','guard_name'];

    public function permissions()
    {
        return $this->belongsToMany(PermissionNew::class, 'role_permission_new', 'role_id', 'permission_id');
    }
}
