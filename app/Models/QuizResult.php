<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizResult extends Model {
    use HasFactory;
    public function quiz() {
        return $this->belongsTo( Quiz::class, 'quiz_id' );
    }
}
