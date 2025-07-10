<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDeduction extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function deductionDetail()
    {
        return $this->belongsTo(EmployeeDeductionDetail::class, 'id');
    }

    public function deductionDetails()
    {
        return $this->hasMany(EmployeeDeductionDetail::class, 'deduction_id');
    }
}
