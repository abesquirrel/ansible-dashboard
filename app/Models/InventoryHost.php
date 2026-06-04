<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryHost extends Model
{
    protected $fillable = [
        'hostname', 'ip_address', 'groups', 'vars',
        'last_ping', 'ping_status', 'ansible_facts',
    ];

    protected $casts = [
        'groups'        => 'array',
        'vars'          => 'array',
        'ansible_facts' => 'array',
        'last_ping'     => 'datetime',
    ];
}
