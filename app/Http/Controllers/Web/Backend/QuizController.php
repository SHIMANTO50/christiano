<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class QuizController extends Controller {
    /**
     * Get all data in table view
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function index( Request $request ) {
        if ( User::find( auth()->user()->id )->hasPermissionTo( 'quiz menu' ) ) {
            if ( $request->ajax() ) {
                $data = Quiz::with( 'course', 'module', 'quistions' )->latest();
                return DataTables::of( $data )
                    ->addIndexColumn()
                    ->addColumn( 'questions', function ( $data ) {

                        return '<span class="badge bg-success fs-5 me-1">' . count( $data->quistions ) . '</span>
                    <a href="' . route( 'question.create', ['quiz_id' => $data->id] ) . '" title="Add Question" class="badge bg-primary fs-5">Add<a>';
                    } )
                    ->addColumn( 'pass_mark', function ( $data ) {

                        return '<span class="badge bg-primary fs-5">' . number_format( $data->pass_mark, 2, '.', ',' ) . '</span>';
                    } )
                    ->addColumn( 'status', function ( $data ) {
                        $status = ' <div class="form-check form-switch d-flex justify-content-center align-items-center">';
                        $status .= ' <input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status"';
                        if ( $data->status == 1 ) {
                            $status .= "checked";
                        }
                        $status .= '><label for="customSwitch' . $data->id . '" class="form-check-label" for="customSwitch"></label></div>';

                        return $status;
                    } )
                    ->addColumn( 'action', function ( $data ) {
                        $user = User::find( auth()->user()->id );
                        $html = '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">';
                        if ( !$user->hasPermissionTo( 'edit quiz' ) && !$user->hasPermissionTo( 'delete quiz' ) ) {
                            $html .= "<span class='text-light bg-danger p-1 rounded-3'>No access</span>";
                        }
                        if ( $user->hasPermissionTo( 'edit quiz' ) ) {
                            $html .= '<a href="' . route( 'quiz.edit', $data->id ) . '" class="btn btn-sm btn-success"><i class="bx bxs-edit"></i></a>';
                        }
                        if ( $user->hasPermissionTo( 'delete quiz' ) ) {
                            $html .= '<a href="#" onclick="showDeleteConfirm(' . $data->id . ')" type="button"
                                                class="btn btn-danger btn-sm text-white" title="Delete" readonly>
                                                <i class="bx bxs-trash"></i>
                                            </a>';
                        }
                        $html .= '</div>';
                        return $html;
                    } )
                    ->rawColumns( ['pass_mark', 'status', 'questions', 'action'] )
                    ->make( true );
            }
            return view( 'backend.layout.quiz.index' );
        }
        return redirect()->back();

    }

    /**
     * Insert View
     *
     * @param Request $request
     */
    public function create(): View {
        if ( User::find( auth()->user()->id )->hasPermissionTo( 'create quiz' ) ) {
            $courses = Course::all();
            return view( 'backend.layout.quiz.create', compact( 'courses' ) );
        }
        return redirect()->back();

    }

    /**
     * Question Create Insert View
     *
     * @param Request $request
     */
    public function questionCreate( $id ): View {
        if ( User::find( auth()->user()->id )->hasPermissionTo( 'create quiz' ) ) {
            $quiz = Quiz::where( 'id', $id )->first();
            $courses = Course::all();
            $modules = CourseModule::all();
            return view( 'backend.layout.quiz.create-question', compact( 'quiz', 'courses', 'modules' ) );
        }
        return redirect()->back();

    }
    /**
     * Store data
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store( Request $request ) {
        if ( User::find( auth()->user()->id )->hasPermissionTo( 'create quiz' ) ) {
            if ( $request->quiz_id ) {

                $validator = Validator::make( $request->all(), [
                    'quiz_id'        => 'required|numeric|exists:quizzes,id',
                    'question.*'     => 'required|string',
                    'type.*'         => 'required|numeric|max:2',
                    'option_one.*'   => 'nullable|string',
                    'option_two.*'   => 'nullable|string',
                    'option_three.*' => 'nullable|string',
                    'option_four.*'  => 'nullable|string',
                    'answer.*'       => 'required|string',
                    'note.*'         => 'nullable|string',
                ] );

            } else {
                $validator = Validator::make( $request->all(), [
                    'title'          => 'required|string',
                    'time'           => 'required|numeric',
                    'course_id'      => 'required|numeric|exists:courses,id',
                    'module_id'      => 'required|numeric|exists:course_modules,id',
                    'question.*'     => 'required|string',
                    'type.*'         => 'required|numeric|max:2',
                    'option_one.*'   => 'nullable|string',
                    'option_two.*'   => 'nullable|string',
                    'option_three.*' => 'nullable|string',
                    'option_four.*'  => 'nullable|string',
                    'answer.*'       => 'required|string',
                    'note.*'         => 'nullable|string',
                ] );
            }

            if ( $validator->fails() ) {
                $errorMessages = $validator->errors()->first();
                return redirect()->back()->with( 't-error', $errorMessages );
            }

            if ( $request->quiz_id == null ) {

                // Slug Check
                $slug = Quiz::where( 'slug', Str::slug( $request->title ) )->first();
                $slug_data = '';

                if ( $slug ) {
                    // random string generator
                    $randomString = Str::random( 5 );
                    $slug_data = Str::slug( $request->title ) . $randomString;
                } else {
                    $slug_data = Str::slug( $request->title );
                }
            }

            // Store data in database

            if ( $request->quiz_id ) {
                $quiz = Quiz::find( $request->quiz_id );
            } else {
                // Quize Insert
                $quiz = new Quiz();
                $quiz->title = $request->title;
                $quiz->slug = $slug_data;
                $quiz->time = $request->time;
                $quiz->course_id = $request->course_id;
                $quiz->course_module_id = $request->module_id;
                $quiz->save();
            }

            // Iterate over questions and insert them into the database
            foreach ( $request->question as $key => $questionStr ) {

                // Question insert
                $question = new Question();
                $question->quiz_id = $quiz->id;
                $question->question = $questionStr;
                $question->type = $request->type[$key];

                if ( $request->type[$key] == 2 ) {
                    // Option one Check
                    $question->option_one = $request->option_one[$key] ?? null;
                    // Option two Check
                    $question->option_two = $request->option_two[$key] ?? null;

                    // Option Three Check
                    $question->option_three = $request->option_three[$key] ?? null;

                    // Option Four Check
                    $question->option_four = $request->option_four[$key] ?? null;

                } else {
                    // Option one Check
                    if ( $request->option_one[$key] != null ) {
                        $question->option_one = $request->option_one[$key];
                    } else {
                        return redirect()->back()->with( 't-error', " Your [$key] Option One Are Required" );
                    }
                    // Option two Check
                    if ( $request->option_two[$key] != null ) {
                        $question->option_two = $request->option_two[$key];
                    } else {
                        return redirect()->back()->with( 't-error', " Your [$key] Option Two Are Required" );
                    }
                    // Option Three Check
                    if ( $request->option_three[$key] != null ) {
                        $question->option_three = $request->option_three[$key];
                    } else {
                        return redirect()->back()->with( 't-error', " Your [$key] Option Three Are Required" );
                    }
                    // Option Four Check
                    if ( $request->option_four[$key] != null ) {
                        $question->option_four = $request->option_four[$key];
                    } else {
                        return redirect()->back()->with( 't-error', " Your [$key] Option Four Are Required" );
                    }
                }

                $question->answer = $request->answer[$key];

                // Note Check
                $question->note = $request->note[$key] ?? null;
                $question->save();
            }

            if ( $request->quiz_id ) {
                $questions = Question::where( 'quiz_id', $request->quiz_id )->get();
                if ( count( $questions ) > 0 ) {

                    $countQuestion = count( $questions ) * 2;

                    // 30 % Pass mark
                    $passMark = $countQuestion / 100 * 30;

                    $quizMarkUpdate = Quiz::where( 'id', $request->quiz_id )->first();
                    $quizMarkUpdate->pass_mark = $passMark;
                    $quizMarkUpdate->save();
                }
            } else {
                $questions = count( $request->question ) * 2;
                // 30 % Pass mark
                $passMark = $questions / 100 * 30;

                $quizMarkUpdate = Quiz::where( 'id', $quiz->id )->first();
                $quizMarkUpdate->pass_mark = $passMark;
                $quizMarkUpdate->save();
            }

            return redirect( route( 'quiz.index' ) )->with( 't-success', 'Quiz added successfully.' );
        }
        return redirect()->back();

    }

    /**
     * Get Selected item data
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit( $id ) {
        if ( User::find( auth()->user()->id )->hasPermissionTo( 'edit quiz' ) ) {
            $quiz = Quiz::with( 'quistions' )->where( 'id', $id )->first();
            $courses = Course::all();
            $modules = CourseModule::all();
            return view( 'backend.layout.quiz.update', compact( 'quiz', 'courses', 'modules' ) );
        }
        return redirect()->back();

    }

    /**
     * Update selected item in database
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update( Request $request ) {
        if ( User::find( auth()->user()->id )->hasPermissionTo( 'edit quiz' ) ) {
            $validator = Validator::make( $request->all(), [
                'title'     => 'required|string',
                'time'      => 'required|numeric',
                'course_id' => 'required|numeric|exists:courses,id',
                'module_id' => 'required|numeric|exists:course_modules,id',
                'quiz_id'   => 'required|numeric|exists:quizzes,id',
            ] );

            if ( $validator->fails() ) {
                $errorMessages = $validator->errors()->first();
                return redirect()->back()->with( 't-error', $errorMessages );
            }

            try {
                $quiz = Quiz::find( $request->quiz_id );
                $quiz->title = $request->title;
                $quiz->time = $request->time;
                $quiz->course_id = $request->course_id;
                $quiz->course_module_id = $request->module_id;
                $quiz->save();
                return redirect()->back()->with( 't-success', "Quiz Updated" );
            } catch ( Exception $e ) {
                return redirect()->back()->with( 't-error', $e->getMessage() );
            }
        }
        return redirect()->back();
        // Update Just a Quize

    }

    /**
     * Update Single Question
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateQuestion( Request $request ) {
        if ( User::find( auth()->user()->id )->hasPermissionTo( 'edit quiz' ) ) {
            // Update Just a Question
            $validator = Validator::make( $request->all(), [
                'question'     => 'required|string',
                'type'         => 'required|numeric|max:2',
                'option_one'   => 'nullable|string',
                'option_two'   => 'nullable|string',
                'option_three' => 'nullable|string',
                'option_four'  => 'nullable|string',
                'answer'       => 'required|string',
                'note'         => 'nullable|string',
            ] );

            if ( $validator->fails() ) {
                $errorMessages = $validator->errors()->first();
                return redirect()->back()->with( 't-error', $errorMessages );
            }

            try {

                // Question insert
                $question = Question::where( 'id', $request->question_id )->first();
                $question->question = $request->question;
                $question->type = $request->type;

                if ( $request->type == 2 ) {
                    // Option one Check
                    $question->option_one = $request->option_one ?? null;
                    // Option two Check
                    $question->option_two = $request->option_two ?? null;

                    // Option Three Check
                    $question->option_three = $request->option_three ?? null;

                    // Option Four Check
                    $question->option_four = $request->option_four ?? null;

                } else {
                    // Option one Check
                    if ( $request->option_one != null ) {
                        $question->option_one = $request->option_one;
                    } else {
                        return redirect()->back()->with( 't-error', " Your Option One Are Required" );
                    }
                    // Option two Check
                    if ( $request->option_two != null ) {
                        $question->option_two = $request->option_two;
                    } else {
                        return redirect()->back()->with( 't-error', " Your Option Two Are Required" );
                    }
                    // Option Three Check
                    if ( $request->option_three != null ) {
                        $question->option_three = $request->option_three;
                    } else {
                        return redirect()->back()->with( 't-error', " Your Option Three Are Required" );
                    }
                    // Option Four Check
                    if ( $request->option_four != null ) {
                        $question->option_four = $request->option_four;
                    } else {
                        return redirect()->back()->with( 't-error', " Your Option Four Are Required" );
                    }
                }

                $question->answer = $request->answer;

                // Note Check
                $question->note = $request->note ?? null;
                $question->save();

                $questions = Question::where( 'quiz_id', $question->quiz_id )->get();
                if ( count( $questions ) > 0 ) {

                    $countQuestion = count( $questions ) * 2;

                    // 30 % Pass mark
                    $passMark = $countQuestion / 100 * 30;

                    $quizMarkUpdate = Quiz::where( 'id', $question->quiz_id )->first();
                    $quizMarkUpdate->pass_mark = $passMark;
                    $quizMarkUpdate->save();
                }

                return redirect()->back()->with( 't-success', "Answer Updated" );
            } catch ( Exception $e ) {
                return redirect()->back()->with( 't-error', $e->getMessage() );
            }
        }
        return redirect()->back();

    }

    /**
     * Delete selected item
     * @param Request $request
     * @param $id
     */
    public function destroy( Request $request, $id ) {
        if ( User::find( auth()->user()->id )->hasPermissionTo( 'delete quiz' ) ) {
            if ( $request->ajax() ) {
                $data = Quiz::findOrFail( $id );

                if ( $data ) {
                    $questions = Question::where( 'quiz_id', $id )->get();
                    if ( count( $questions ) > 0 ) {
                        foreach ( $questions as $questiion ) {
                            $questiion->delete();
                        }
                    }

                    $data->delete();
                }
                return response()->json( [
                    'success' => true,
                    'message' => 'Quiz Deleted With All Questions Successfully.',
                ] );
            }
        }
        return redirect()->back();

    }

    /**
     * Delete selected item
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function questionDestroy( Request $request, $id ) {
        if ( User::find( auth()->user()->id )->hasPermissionTo( 'delete quiz' ) ) {
            if ( $request->ajax() ) {
                $data = Question::with( 'quiz' )->findOrFail( $id );
                if ( $data ) {
                    $data->delete();
                    $questions = Question::where( 'quiz_id', $data->quiz->id )->get();
                    if ( count( $questions ) > 0 ) {

                        $countQuestion = count( $questions ) * 2;

                        // 30 % Pass mark
                        $passMark = $countQuestion / 100 * 30;

                        $quizMarkUpdate = Quiz::where( 'id', $data->quiz->id )->first();
                        $quizMarkUpdate->pass_mark = $passMark;
                        $quizMarkUpdate->save();
                    }
                }
                return response()->json( [
                    'success' => true,
                    'message' => 'Quiestion Deleted Successfully.',
                ] );
            }
        }
        return redirect()->back();

    }

    /**
     * Change Data the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function status( $id ) {
        $data = Quiz::where( 'id', $id )->first();
        if ( $data->status == 1 ) {
            $data->status = '0';
            $data->save();
            return response()->json( [
                'success' => false,
                'message' => 'Unpublished Successfully.',
            ] );
        } else {
            $data->status = '1';
            $data->save();
            return response()->json( [
                'success' => true,
                'message' => 'Published Successfully.',
            ] );
        }
    }

    /**
     * Get Module For Course
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModule( $id ) {
        $data = CourseModule::where( 'course_id', $id )->get();
        if ( $data != null ) {
            return response()->json( [
                'success' => true,
                'message' => 'Getting Module.',
                'data'    => $data,
            ] );
        } else {
            return response()->json( [
                'success' => false,
                'message' => 'Module Not Found',
                'data'    => null,
            ] );
        }
    }

}
