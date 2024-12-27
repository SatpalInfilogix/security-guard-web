<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class, 'guard_id');
    }

    public function deductionDetail()
    {
        return $this->belongsTo(DeductionDetail::class, 'id');
    }
}
