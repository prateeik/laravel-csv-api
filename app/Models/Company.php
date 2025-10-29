<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $fillable = ['company_name', 'email', 'phone_number', 'is_duplicate'];

    public function duplicates()
    {
        return $this->hasMany(CompanyDuplicate::class);
    }
}
