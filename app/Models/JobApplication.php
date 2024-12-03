<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    // Get Job
    public function job(){
        return $this->belongsTo(JobPost::class, 'job_post_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
