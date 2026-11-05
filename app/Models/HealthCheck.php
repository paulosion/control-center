<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCheck extends Model
{
    public $timestamps = false;

    protected $fillable = ['system', 'ok', 'status', 'ms', 'error', 'checked_at'];

    protected function casts(): array
    {
        return ['ok' => 'boolean', 'checked_at' => 'datetime'];
    }
}
