<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class RoleMenuPermission extends Model
{


    protected $fillable = [
        'role_id',
        'menu_id',
        'can_view',
        'can_add',
        'can_edit',
        'can_delete'
    ];
}

