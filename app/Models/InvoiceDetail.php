<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function guardType()
    {
        return $this->belongsTo(RateMaster::class, 'guard_type_id');
    }
}
