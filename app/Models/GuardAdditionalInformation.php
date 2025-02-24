<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuardAdditionalInformation extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function rateMaster()
    {
        return $this->belongsTo(RateMaster::class, 'guard_employee_as_id');
    }
}
