<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDeductionDetail extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function deduction()
    {
        return $this->belongsTo(EmployeeDeduction::class, 'deduction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
