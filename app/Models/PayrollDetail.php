<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class, 'guard_id');
    }

    public function guardType()
    {
        return $this->belongsTo(RateMaster::class, 'guard_type_id');
    }
}
