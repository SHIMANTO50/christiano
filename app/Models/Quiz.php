<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model {
    use HasFactory;

    public function course() {
        return $this->belongsTo( Course::class );
    }
    public function module() {
        return $this->belongsTo( CourseModule::class, 'course_module_id' );
    }

    public function quistions() {
        return $this->hasMany( Question::class, 'quiz_id' );
    }
}
