<?php

namespace App\Http\Controllers\API;

use App\Models\Course;
use App\Traits\apiresponse;
use App\Models\CourseModule;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CourseController extends Controller
{
    //$data = Course::with( 'category' )->latest();
    use apiresponse;

    public function index()
    {
        $courses = Course::all();
        return $this->success($courses, 'Course Certification retrieved successfully', 200);
    }

    public function courseModulesUnderCourseId($courseId)
    {
        $course = Course::with('course_modules')->find($courseId);
        // Check if the course exists
        if (!$course) {
            return $this->error('Course not found', 404);
        }

        // Return the response with the specific course and its modules
        return $this->success($course, 'Course and Modules retrieved successfully', 200);
    }

    public function courseContentsUnderModuleId($moduleId)
    {
        // Retrieve the module with its associated contents by module ID
        $module = CourseModule::with('contents')->find($moduleId);

        // Check if the module exists
        if (!$module) {
            return $this->error('Course Module not found', 404);
        }

        // Return the response with the specific module and its contents
        return $this->success($module, 'Course Module and Contents retrieved successfully', 200);
    }

    public function courseFilesUnderCourseId($courseId)
    {
        // $course = Course::with('course_modules.course_contents.files')->find($courseId);

        // if (!$course) {
        //     return $this->error('Course not found', 404);
        // }

        // return $this->success($course, 'Course files retrieved successfully', 200);

        $course = Course::with('course_modules.course_contents.files')->find($courseId);

        // Check if the course exists
        if (!$course) {
            return $this->error('Course not found', 404);
        }

        // Extract only the files from the nested structure
        $files = $course->course_modules->flatMap(function ($module) {
            return $module->course_contents->flatMap(function ($content) {
                return $content->files;
            });
        });

        // Return the files information
        return $this->success($files, 'Course files retrieved successfully', 200);
    }
}
