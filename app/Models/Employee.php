<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
     'name', 'employee_id', 'address', 'email', 'phone'
    ];

    public function commissions()
    {
        return $this->hasMany(EmployeeCommission::class, 'employee_id', 'id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'employee_id', 'id');
    }
}
