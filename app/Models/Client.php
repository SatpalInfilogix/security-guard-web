<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ClientSite;

class Client extends Model
{
    use HasFactory;
    protected $guarded =[];

    public function clientSites()
    {
        return $this->hasMany(ClientSite::class);
    }
}
