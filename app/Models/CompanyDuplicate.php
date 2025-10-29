<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDuplicate extends Model
{
    use HasFactory;
    protected $fillable = ['company_id', 'duplicate_company_name', 'duplicate_email', 'duplicate_phone_number'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
