<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertimeMain extends Model
{
    use HasFactory;
    protected $table = 'employee_overtimes_main';
    protected $guarded  = [];

    public function detail(){
        return $this->hasMany(EmployeeOvertime::class,'employee_overtime_main_id');
    }

    public function employee(){
        return $this->hasOne(User::class,'id','employee_id');
    }
}
