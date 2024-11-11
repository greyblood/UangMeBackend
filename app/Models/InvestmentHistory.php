<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'bank',
        'va_number',
    ];
}
