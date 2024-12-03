<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Traits\apiresponse;


class CourseController extends Controller
{
    //$data = Course::with( 'category' )->latest();
    use apiresponse;

    public function index()
   {
       $myJournals = Course::with('category')->get();
       return $this->success($myJournals, 'My Jorunal retrieved successfully', 200);
   }

}
