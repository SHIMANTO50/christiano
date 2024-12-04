<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Helper\Helper;
use App\Models\Course;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\CourseModule;
use Illuminate\Http\Request;
use App\Models\CourseContent;
use App\Models\CourseContentFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Notifications\UserNotification;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Get all data in table view
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if (User::find(auth()->user()->id)->hasPermissionTo('course menu')) {
            // $data = Course::with( 'category' );
            // dd($data);
            if ($request->ajax()) {
                $data = Course::all();

                return DataTables::of($data)
                    ->addIndexColumn()

                    ->addColumn('course_feature_image', function ($data) {
                        $feature_image = url($data->course_feature_image);
                        return '<div class="avatar avatar-lg"><img class="avatar-img img-fluid" style="border-radius: 10px;" src="' . $feature_image . '" alt="' . $data->course_title . '"></div>';
                    })
                    ->addColumn('last_update', function ($data) {
                        return Carbon::parse($data->last_update)->format('m-d-Y');
                    })
                    ->addColumn('course_price', function ($data) {
                        return "<span class='bg-info rounded py-1 px-3 text-light me-1'>$" . $data->course_price . '</span>';
                    })
                    ->addColumn('status', function ($data) {
                        $status = ' <div class="form-check form-switch d-flex justify-content-center align-items-center">';
                        $status .= ' <input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status"';
                        if ($data->status == 1) {
                            $status .= 'checked';
                        }
                        $status .= '><label for="customSwitch' . $data->id . '" class="form-check-label" for="customSwitch"></label></div>';

                        return $status;
                    })
                    ->addColumn('action', function ($data) {
                        $user = User::find(auth()->user()->id);
                        $html = '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">';
                        if (!$user->hasPermissionTo('edit course') && !$user->hasPermissionTo('delete course')) {
                            $html .= "<span class='text-light bg-danger p-1 rounded-3'>No access</span>";
                        }
                        if ($user->hasPermissionTo('edit course')) {
                            $html .= '<a href="' . route('course.edit', $data->id) . '" class="btn btn-sm btn-success"><i class="bx bxs-edit"></i></a>';
                        }
                        if ($user->hasPermissionTo('delete course')) {
                            $html .=
                                '<a href="#" onclick="showDeleteConfirm(' .
                                $data->id .
                                ')" type="button"
                                        class="btn btn-danger btn-sm text-white" title="Delete" readonly>
                                        <i class="bx bxs-trash"></i>
                                    </a>';
                        }
                        $html .= '</div>';
                        return $html;
                    })
                    ->rawColumns(['course_price', 'course_feature_image', 'last_update', 'status', 'action'])
                    ->make(true);
            }
            return view('backend.layout.course.index');
        }
        return redirect()->back();
    }
    /**
     * Insert View
     *
     * @param Request $request
     * @return Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        if (User::find(auth()->user()->id)->hasPermissionTo('create course')) {
            $categories = Category::where('status', '1')->orderBy('category_name')->get();
            return view('backend.layout.course.create', compact('categories'));
        }
        return redirect()->back();
    }
    /**
     * Store data
     *
     * @param Request $request
     */
    public function store(Request $request)
    {
        //dd($request->all());
        if (User::find(auth()->user()->id)->hasPermissionTo('create course')) {
            //validation rules array
            $rules = [
                'course_title' => 'required|string|max:255|unique:courses,course_title',
                'course_price' => 'required|numeric',
                'summary' => 'required|string',
                'course_feature_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'module_number.*' => 'required|integer',
                'module_titles.*' => 'required|string|max:255',
            ];
            //course module content validation rules added into rules array
            foreach ($request->module_number as $key => $moduleNumber) {
                $rules["module_{$moduleNumber}_content_title.*"] = 'required|string|max:255';
                $rules["module_{$moduleNumber}_content_length.*"] = 'required|nullable|string|max:255';
                $rules["module_{$moduleNumber}_video_file.*"] = 'nullable|mimes:mp4,avi,mkv,webm|max:51200';
                $rules["module_{$moduleNumber}_files.*"] = 'nullable|mimes:mp4,avi,mkv,webm|max:51200';
                $rules["module_{$moduleNumber}_files.*"] = 'nullable|file|mimes:pdf,xlsx,xls,doc,docx,mp4,avi,mkv,webm|max:51200';

            }

            $validation = Validator::make($request->all(), $rules);
            if ($validation->validated()) {
               //dd($request->all());
                //dd("Hello World");
                $users = User::all();
                // Slug Check
                $slug = Course::where('course_slug', Str::slug($request->course_title))->first();
                $slug_data = '';

                if ($slug) {
                    // random string generator
                    $randomString = Str::random(5);
                    $slug_data = Str::slug($request->course_title) . $randomString;
                } else {
                    $slug_data = Str::slug($request->course_title);
                }
                // random string generator
                $randomString = Str::random(20);
                // Image store in local
                $featuredImage = Helper::fileUpload($request->file('course_feature_image'), 'course', $request->course_feature_image . '_' . $randomString);

                // Store data in database
                //  try {
                DB::beginTransaction();
                //store course
                $course = new Course();
                $course->course_title = $request->course_title;
                $course->course_slug = $slug_data;
                //$course->feature_video = $request->feature_video;
                //$course->level = $request->level;
                //$course->category_id = $request->category_id;
                $course->course_price = $request->course_price;
                $course->summary = $request->summary;
                $course->course_feature_image = $featuredImage;
                $course->save();

                //store course module
                foreach ($request->module_titles as $index => $title) {
                    $moduleNumber = $request['module_number'][$index];
                    $courseModule = new CourseModule();
                    $courseModule->course_module_name = $title;
                    $courseModule->course_id = $course->id;
                    $courseModule->save();
                    //Store module content
                    foreach ($request["module_{$moduleNumber}_content_title"] as $i => $title) {
                        //all content length store a array
                        $contentLengthArray[] = $request["module_{$moduleNumber}_content_length"][$i];

                        $courseContent = new CourseContent();
                        $courseContent->content_title = $title;
                        $courseContent->content_length = $request["module_{$moduleNumber}_content_length"][$i];
                        // Handle video file upload if exists
                        if ($request->hasFile("module_{$moduleNumber}_video_file") && $request->file("module_{$moduleNumber}_video_file")[$i]) {
                            $videoFile = $request->file("module_{$moduleNumber}_video_file")[$i];
                            $videoFileName = $randomString . '_' . time() . '.' . $videoFile->getClientOriginalExtension();
                            $courseContent->video_file = Helper::fileUpload($videoFile, 'videos', $videoFileName);
                        }
                        //$courseContent->video_file = $request["module_{$moduleNumber}_video_file"][$i];
                        $courseContent->course_id = $course->id;
                        $courseContent->course_module_id = $courseModule->id;
                        $courseContent->save();

                        //dd($request["module_{$moduleNumber}_files"]);
                        // Handle multiple file uploads (PDFs, Excel files)
                        if ($request->hasFile("module_{$moduleNumber}_files") && isset($request["module_{$moduleNumber}_files"])) {
                            $files = $request->file("module_{$moduleNumber}_files"); // Multiple files
                            //dd($files);

                            if (is_array($files)) {
                                foreach ($files as $fileindex => $file) {
                                    if ($file->isValid()) {
                                        $randomString = Str::random(20);
                                        $fileExtension = $file->getClientOriginalExtension();
                                        $fileName = $randomString . '_' . Str::uuid() . '.' . $fileExtension;
                                        $filePath = Helper::fileUpload($file, 'course_files', $fileName);

                                        // Store file in the `course_content_files` table
                                        CourseContentFile::create([
                                            'course_content_id' => $courseContent->id,
                                            'file_path' => $filePath,
                                            'file_type' => $fileExtension, // PDF, Excel, etc.
                                        ]);
                                    }
                                }
                            } else {
                                // Handle a single file
                                $file = $files;
                                if ($file->isValid()) {
                                    $fileExtension = $file->getClientOriginalExtension();
                                    $fileName = $randomString . '_' . time() . '.' . $fileExtension;
                                    $filePath = Helper::fileUpload($file, 'course_files', $fileName);

                                    // Store file in the `course_content_files` table
                                    CourseContentFile::create([
                                        'course_content_id' => $courseContent->id,
                                        'file_path' => $filePath,
                                        'file_type' => $fileExtension, // PDF, Excel, etc.
                                    ]);
                                }
                            }
                        }
                    }
                }
                //course content length array summing
                $courseDuration = Helper::addDurationsArray($contentLengthArray);
                //course duration updated
                $course->update(['duration' => $courseDuration]);
                foreach ($users as $user) {
                    if ($user->id != Auth::user()->id && 2 == $user->user_type) {
                        $user->notify(new UserNotification('Admin: Release New Course', " $course->course_title", route('course.enrollment', $course->id)));
                    }
                }
                DB::commit();
                return redirect(route('course.index'))->with('t-success', 'Course added successfully.');

                // } /*catch ( Exception $e ) {
                //     DB::rollBack();
                //     return redirect( route( 'course.create' ) )->with( 't-error', 'Something Went Wrong' );
                // }*/
            } else {
                //dd($validation->errors());
                return $validation->errors();
            }
        }
        return redirect()->back();
    }
    /**
     * Get Selected item data
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        if (User::find(auth()->user()->id)->hasPermissionTo('edit course')) {
            $categories = Category::where('status', '1')->orderBy('category_name')->get();
            $course = Course::with('course_modules')->where('id', $id)->first();
            // return $course;
            return view('backend.layout.course.update', compact('course', 'categories'));
        }
        return redirect()->back();
    }

    /**
     * Update selected item in database
     *
     * @param Request $request
     */
    public function update(Request $request)
    {
        if (User::find(auth()->user()->id)->hasPermissionTo('edit course')) {
            //validation rules array
            $rules = [
                'course_title' => 'required|string|max:255|unique:courses,course_title,' . $request->id . 'id,',
                'level' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'course_price' => 'required|numeric',
                'feature_video' => 'required|url',
                'summary' => 'required|string',
                'course_feature_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'module_number.*' => 'required|integer',
                'module_titles.*' => 'required|string|max:255',
            ];
            //course module content validation rules added into rules array
            foreach ($request->module_number as $key => $moduleNumber) {
                $rules["module_{$moduleNumber}_content_title.*"] = 'required|string|max:255';
                $rules["module_{$moduleNumber}_video_source.*"] = 'required';
                $rules["module_{$moduleNumber}_video_url.*"] = 'required|nullable|url';
                $rules["module_{$moduleNumber}_content_length.*"] = 'required|nullable|string|max:255';
            }
            $validation = Validator::make($request->all(), $rules);
            if ($validation->validated()) {
                $users = User::all();
                // Slug Check
                $slug = Course::where('course_slug', Str::slug($request->course_title))->first();
                $slug_data = '';

                if ($slug) {
                    // random string generator
                    $randomString = Str::random(5);
                    $slug_data = Str::slug($request->course_title) . $randomString;
                } else {
                    $slug_data = Str::slug($request->course_title);
                }

                // Update data in database
                try {
                    DB::beginTransaction();
                    //store course
                    $course = Course::where('id', $request->id)->first();
                    $course->course_title = $request->course_title;
                    $course->feature_video = $request->feature_video;
                    $course->course_slug = $slug_data;
                    $course->level = $request->level;
                    $course->last_update = Carbon::now()->format('Y-m-d H:i:s');
                    $course->category_id = $request->category_id;
                    $course->course_price = $request->course_price;
                    $course->summary = $request->summary;
                    // Check Image Update
                    if ($request->course_feature_image != null) {
                        // Remove old image
                        if (File::exists($course->course_feature_image)) {
                            File::delete($course->course_feature_image);
                        }
                        // random string generator
                        $randomString = Str::random(20);
                        // Image store in local
                        $featuredImage = Helper::fileUpload($request->file('course_feature_image'), 'course', $request->course_feature_image . '_' . $randomString);
                        $course->course_feature_image = $featuredImage;
                    }
                    //update course module
                    foreach ($request->module_titles as $index => $title) {
                        $moduleNumber = $request['module_number'][$index];
                        $courseModule = CourseModule::updateOrCreate(
                            [
                                'id' => $request['module_id_list'][$index],
                            ],
                            [
                                'course_module_name' => $title,
                                'course_id' => $course->id,
                            ],
                        );
                        //Store module content
                        foreach ($request["module_{$moduleNumber}_content_title"] as $i => $title) {
                            $contentLengthArray[] = $request["module_{$moduleNumber}_content_length"][$i];
                            CourseContent::updateOrCreate(
                                [
                                    'id' => $request["module_{$moduleNumber}_content_id_list"][$i],
                                ],
                                [
                                    'content_title' => $title,
                                    'video_source' => $request["module_{$moduleNumber}_video_source"][$i],
                                    'video_url' => $request["module_{$moduleNumber}_video_url"][$i],
                                    'content_length' => $request["module_{$moduleNumber}_content_length"][$i],
                                    'course_id' => $course->id,
                                    'course_module_id' => $courseModule->id,
                                ],
                            );
                        }
                    }
                    $courseDuration = Helper::addDurationsArray($contentLengthArray);
                    $course->duration = $courseDuration;
                    $course->save();
                    foreach ($users as $user) {
                        if ($user->id != Auth::user()->id && 2 == $user->user_type) {
                            $user->notify(new UserNotification('Admin: Update Course', " $course->course_title", route('course.enrollment', $course->id)));
                        }
                    }
                    DB::commit();
                    return redirect(route('course.index'))->with('t-success', 'Course Edit successfully.');
                } catch (Exception $e) {
                    DB::rollBack();
                    return redirect(route('course.create'))->with('t-error', 'Something Went Wrong');
                }
            }
        }
        return redirect()->back();
    }

    /**
     * Delete selected Course item
     * @param Request $request
     * @param $id
     */
    public function destroy(Request $request, $id)
    {
        if (User::find(auth()->user()->id)->hasPermissionTo('delete course')) {
            try {
                if ($request->ajax()) {
                    DB::beginTransaction();
                    CourseContent::where('course_id', $id)->delete();
                    CourseModule::where('course_id', $id)->delete();
                    $course = Course::findOrFail($id);
                    if ($course->course_feature_image != null) {
                        // Remove image
                        if (File::exists($course->course_feature_image)) {
                            File::delete($course->course_feature_image);
                        }
                    }
                    $course->delete();
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Course Deleted Successfully.',
                    ]);
                }
            } catch (Exception $th) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong',
                ]);
            }
        }
        return redirect()->back();
    }

    /**
     * Course Status Change.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function status($id)
    {
        $data = Course::where('id', $id)->first();
        if ($data->status == 1) {
            $data->status = '0';
            $data->save();
            return response()->json([
                'success' => false,
                'message' => 'Unpublished Successfully.',
            ]);
        } else {
            $data->status = '1';
            $data->save();
            return response()->json([
                'success' => true,
                'message' => 'Published Successfully.',
            ]);
        }
    }

    /**
     * Delete Module item
     * @param Request $request
     * @param $id
     */
    public function moduleDestroy(Request $request)
    {
        if (User::find(auth()->user()->id)->hasPermissionTo('delete course')) {
            try {
                if ($request->ajax()) {
                    DB::beginTransaction();
                    $contents = CourseContent::where(['course_id' => $request->course_id, 'course_module_id' => $request->module_id])->get();
                    foreach ($contents as $content) {
                        $contentLengthArray[] = $content['content_length'];
                        $content->delete();
                    }
                    $courseDuration = Helper::addDurationsArray($contentLengthArray);
                    $course = Course::where('id', $request->course_id)->first();

                    $updateDuration = Helper::subtractDuration($course->duration, $courseDuration);
                    $course->update(['duration' => $updateDuration]);

                    CourseModule::where(['course_id' => $request->course_id, 'id' => $request->module_id])->delete();
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Module Deleted Successfully.',
                    ]);
                }
            } catch (Exception $th) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong',
                ]);
            }
        }
        return redirect()->back();
    }

    /**
     * Content Status Change.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function contentStatus($id)
    {
        $data = CourseContent::where('id', $id)->first();
        if ($data->status == 1) {
            $data->status = '0';
            $data->save();
            return response()->json([
                'success' => false,
                'message' => 'Unpublished Successfully.',
            ]);
        } else {
            $data->status = '1';
            $data->save();
            return response()->json([
                'success' => true,
                'message' => 'Published Successfully.',
            ]);
        }
    }

    /**
     * Delete Content item
     * @param Request $request
     * @param $id
     */
    public function contentDestroy(Request $request)
    {
        if (User::find(auth()->user()->id)->hasPermissionTo('delete course')) {
            try {
                if ($request->ajax()) {
                    DB::beginTransaction();
                    $content = CourseContent::where('id', $request->id)->first();
                    $course = Course::where('id', $request->course_id)->first();

                    $updateDuration = Helper::subtractDuration($course->duration, $content->content_length);

                    $course->update(['duration' => $updateDuration]);
                    $content->delete();
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Content Deleted Successfully.',
                    ]);
                }
            } catch (Exception $th) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Something Went Wrong',
                ]);
            }
        }
        return redirect()->back();
    }
}
