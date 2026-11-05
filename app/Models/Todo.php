<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    public const SYSTEMS = ['my-genone', '201 System', 'PayMaya', 'face-service', 'RFID', 'General'];

    protected $fillable = ['title', 'system', 'done_at'];

    protected function casts(): array
    {
        return ['done_at' => 'datetime'];
    }
}
