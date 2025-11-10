<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'title',
        'description',
        'due_date',
        'priority',
        'completed',
        'user_id',
    ];

    // belongs to a subject
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
