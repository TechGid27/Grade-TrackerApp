<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'target_grade',
        'user_id', 
    ];

    // A subject belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A subject has many assessments
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    // A subject has many todos
    public function todos()
    {
        return $this->hasMany(Todo::class);
    }
}
