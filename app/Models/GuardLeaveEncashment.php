<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; // <- Make sure this is included

class GuardLeaveEncashment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function guardUser()
    {
        return $this->belongsTo(User::class, 'guard_id');
    }
}
