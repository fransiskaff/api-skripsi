<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    protected $fillable = [
        'salesman_id', 
        'target_amount', 
        'month', 
        'year'
        ];

    public function salesman()
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }
}