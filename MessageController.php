<?php

namespace App\Http\Controllers\Api\V1\Engage\Message;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Message\Message;
use App\Models\Message\MessageCompany;
use App\Models\Message\MessageCompanyAdministrator;
use App\Models\Message\MessageCompanyCompany;
use App\Models\Message\MessageCompanyCompanyRecepientType;
use App\Models\Message\MessageCompanyCombinationClass;
use App\Models\Message\MessageUser;
use App\Models\Message\MessageCompanyCompanyRecepient;
use App\Models\SenderName\SenderName;
use App\Models\Recepient\Recepient;
use App\Models\Recepient\RecepientCompany;
use App\Models\Recepient\RecepientCompanyAdministrator;
use App\Models\Recepient\RecepientCompanyCompany;
use App\Models\Recepient\RecepientCompanyCompanyRecepientType;
use App\Models\Recepient\RecepientCompanyCombinationClass;
use App\Models\Recepient\RecepientCompanyCompanyCombinationClass;
use App\Models\Recepient\CompanyRecepientType;
use App\Models\Company\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Location\Country;



class MessageController extends Controller
{
    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }



    /**
     * @OA\Get(
     *     path="/api/engage/messages",
     *     summary="Retrieve all messages",
     *     tags={"Engage | Message"},
     *     security={
     *         {"sanctum": {}},
     *     },
     *     description="Retrieves a list of all messages.",
     *     @OA\Response(
     *         response=200,
     *         description="Messages retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="body", type="string"),
     *                     @OA\Property(property="sender_name_id", type="integer"),
     *                     @OA\Property(
     *                         property="scheduled_at",
     *                         type="object",
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="time", type="string", format="time")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object",
     *                 @OA\Property(property="first", type="string"),
     *                 @OA\Property(property="last", type="string"),
     *                 @OA\Property(property="prev", type="string"),
     *                 @OA\Property(property="next", type="string")
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     * )
     */
    public function index()
    {
        try {
            $messages = $this->message->with([
                'sender_name',
                'users.user',
                'user',
                'message_schools.school',
                'message_schools.message_school_combination_classes.school_combination_class.combination_class.class.class_type',
                'message_schools.message_school_administrators.school_administrator.designation',
                'message_schools.message_school_students.academic',
                'message_schools.message_school_students.message_school_student_recepient_types.student_recepient_type',
                'message_schools.message_school_students.message_school_student_recepient_types.message_school_student_recepients.student',
            ])->orderBy('created_at', 'desc')->get();
            return $this->successResponse('Messages retrieved successfully', $messages);
        } catch (\Exception $e) {

            return $this->errorResponse($e->getMessage(), 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/engage/messages/{id}",
     *     summary="Retrieve a specific message",
     *     tags={"Engage | Message"},
     *     security={
     *         {"sanctum": {}},
     *     },
     *     description="Retrieves details of a specific message by ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the message to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="body", type="string"),
     *             @OA\Property(property="sender_name_id", type="integer"),
     *             @OA\Property(
     *                 property="scheduled_at",
     *                 type="object",
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="time", type="string", format="time")
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     * )
     */
    public function show($id)
    {
        try {
            $message = $this->message->with([
                'sender_name',
                'users.user',
                'user',
                'message_schools.school',
                'message_schools.message_school_combination_classes.school_combination_class.combination_class.class.class_type',
                'message_schools.message_school_administrators.school_administrator.designation',
                'message_schools.message_school_students.academic',
                'message_schools.message_school_students.message_school_student_recepient_types.student_recepient_type',
                'message_schools.message_school_students.message_school_student_recepient_types.message_school_student_recepients.student',
            ])->find($id);

            if (!$message) {
                return $this->errorResponse('Message not found', 404);
            }

            return $this->successResponse('Message retrieved successfully', $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/engage/messages",
     *     summary="Store a new message",
     *     tags={"Engage | Message"},
     *     security={
     *         {"sanctum": {}},
     *     },
     *     description="Deletes a student recepient type by ID.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "body", "sender_name_id", "scheduled_at", "recepients"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="body", type="string"),
     *             @OA\Property(property="sender_name_id", type="integer"),
     *             @OA\Property(
     *                 property="scheduled_at",
     *                 type="object",
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="time", type="string", format="time")
     *             ),
     *             @OA\Property(
     *                 property="recepients",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="users",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="schools",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="school_id", type="integer"),
     *                             @OA\Property(
     *                                 property="administrators",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="id", type="integer")
     *                                 )
     *                             ),
     *                             @OA\Property(
     *                                 property="students",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="academic_id", type="integer"),
     *                                     @OA\Property(
     *                                         property="recepient_types",
     *                                         type="array",
     *                                         @OA\Items(
     *                                             type="object",
     *                                             @OA\Property(property="id", type="integer")
     *                                         )
     *                                     ),
     *                                     @OA\Property(
     *                                         property="combination_classes",
     *                                         type="array",
     *                                         @OA\Items(
     *                                             type="object",
     *                                             @OA\Property(property="school_combination_class_id", type="integer")
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="body", type="string"),
     *             @OA\Property(property="sender_name_id", type="integer"),
     *             @OA\Property(
     *                 property="scheduled_at",
     *                 type="object",
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="time", type="string", format="time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'body' => 'required|string',
            'sender_name_id' => 'required|exists:sender_names,id',
            'scheduled_at.date' => 'required_with:scheduled_at|date_format:Y-m-d',
            'scheduled_at.time' => 'required_with:scheduled_at|date_format:H:i',
            'recepients' => 'required|array',
            'recepients.*.users.*.id' => 'required|exists:users,id',
            'recepients.*.schools.*.school_id' => 'required|exists:schools,id',
            'recepients.*.schools.*.school_administrators.*.id' => 'required|exists:school_administrators,id',
            'recepients.*.schools.*.students.*.academic_id' => 'required|exists:academics,id',
            'recepients.*.schools.*.students.*.recepient_types.*' => 'required|exists:student_recepient_types,id',
            'recepients.*.schools.*.students.*.combination_classes.*.school_combination_class_id' => 'required|exists:school_combination_classes,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        try {
            DB::beginTransaction();

            $message = $this->message->create([
                'title' => $request->title,
                'body' => $request->body,
                'sender_name_id' => $request->sender_name_id,
                'scheduled_at' => empty($request->scheduled_at) ? null : [
                    'date' => $request->scheduled_at['date'],
                    'time' => $request->scheduled_at['time']
                ],
                'user_id' => Auth::user()->id
            ]);

            // Handle recepients
            foreach ($request->recepients as $recipient) {

                foreach ($recipient['users'] as $user) {
                    MessageUser::updateOrCreate(
                        [
                            'user_id' => $user['id'],
                            'message_id' => $message->id
                        ],
                        ['status' => 'pending']
                    );
                }

                foreach ($recipient['schools'] as $school) {

                    $messageCompany = MessageCompany::updateOrCreate(
                        [
                            'school_id' => $school['school_id'],
                            'message_id' => $message->id
                        ],
                        ['status' => 'pending']
                    );

                    foreach ($school['school_administrators'] as $administrator) {
                        MessageCompanyAdministrator::updateOrCreate(
                            ['school_administrator_id' => $administrator['id']],
                            [
                                'message_school_id' => $messageCompany->id
                            ]
                        );
                    }

                    foreach ($school['students'] as $student) {

                        $messageCompanyCompany = MessageCompanyCompany::updateOrCreate(
                            [
                                'message_school_id' => $messageCompany->id,
                                'academic_id' => $student['academic_id']
                            ],
                            [
                                'student_status' => 'active',
                                'student_sponsor_status' => 'pending'
                            ]
                        );


                        foreach ($student['combination_classes'] as $combinationClass) {
                            MessageCompanyCombinationClass::updateOrCreate(
                                [
                                    'school_combination_class_id' => $combinationClass['school_combination_class_id'],
                                    'message_school_id' => $messageCompany->id
                                ],
                                []
                            );
                        }

                        foreach ($student['recepient_types'] as $recepientType) {


                            $studentRecepientType = CompanyRecepientType::where('id', $recepientType['id'])->first();

                            if ($studentRecepientType) {

                                $messageCompanyCompanyRecepientType = MessageCompanyCompanyRecepientType::updateOrCreate(
                                    [
                                        'message_school_student_id' => $messageCompanyCompany->id,
                                        'student_recepient_type_id' => $studentRecepientType->id
                                    ]
                                );

                                $students = Company::whereNotNull($studentRecepientType->key)->get();

                                foreach ($students as $student) {
                                    if (isset($student->{$studentRecepientType->key})) {

                                        $phone = $student->{$studentRecepientType->key};

                                        // check if json
                                        $json = is_array($phone) ? $phone : json_decode($phone, true);

                                        if ($json) {

                                            $code = trim(preg_replace('/\s+/', '', $json['code']));
                                            $number = trim(preg_replace('/\s+/', '', $json['number']));

                                            // check if code exists in country table
                                            $country = Country::where('phone_code', $code)->first();

                                            if ($country) {
                                                // check id number is integer
                                                if (is_int((int)$number)) {

                                                    MessageCompanyCompanyRecepient::updateOrCreate(
                                                        [
                                                            'message_school_student_recepient_type_id' => $messageCompanyCompanyRecepientType->id,
                                                            'student_id' => $student->id
                                                        ],
                                                        [
                                                            'phone' => [
                                                                'code' => $code,
                                                                'number' => $number
                                                            ]
                                                        ]
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();

            return $this->successResponse('Message created successfully', $message, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }





    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'body' => 'required|string',
            'sender_name_id' => 'required|exists:sender_names,id',
            'scheduled_at.date' => 'required_with:scheduled_at|date_format:Y-m-d',
            'scheduled_at.time' => 'required_with:scheduled_at|date_format:H:i',
            'recepients' => 'required|array',
            'recepients.*.users.*.id' => 'required|exists:users,id',
            'recepients.*.schools.*.school_id' => 'required|exists:schools,id',
            'recepients.*.schools.*.school_administrators.*.id' => 'required|exists:school_administrators,id',
            'recepients.*.schools.*.students.*.academic_id' => 'required|exists:academics,id',
            'recepients.*.schools.*.students.*.recepient_types.*' => 'required|exists:student_recepient_types,id',
            'recepients.*.schools.*.students.*.combination_classes.*.school_combination_class_id' => 'required|exists:school_combination_classes,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        try {
            DB::beginTransaction();

            $message = $this->message->fin($id);

            if (!$message) {
                return $this->errorResponse('Message not found', 404);
            }
            $message->update([
                'title' => $request->title,
                'body' => $request->body,
                'sender_name_id' => $request->sender_name_id,
                'scheduled_at' => empty($request->scheduled_at) ? null : [
                    'date' => $request->scheduled_at['date'],
                    'time' => $request->scheduled_at['time']
                ]
            ]);

            $existingRecipients = $message->recipients()->get();
            $newRecipients = collect($request->recepients)->flatMap(function ($recipient) {
                return array_merge($recipient['users'], $recipient['schools']);
            })->pluck('id')->all();
            $existingRecipients->each(function ($existingRecipient) use ($newRecipients) {
                if (!in_array($existingRecipient->id, $newRecipients)) {
                    $existingRecipient->delete();
                }
            });

            foreach ($request->recepients as $recipient) {

                foreach ($recipient['users'] as $user) {
                    MessageUser::updateOrCreate(
                        [
                            'user_id' => $user['id'],
                            'message_id' => $message->id
                        ],
                        ['status' => 'pending']
                    );
                }

                foreach ($recipient['schools'] as $school) {

                    $messageCompany = MessageCompany::updateOrCreate(
                        [
                            'school_id' => $school['school_id'],
                            'message_id' => $message->id
                        ],
                        ['status' => 'pending']
                    );

                    foreach ($school['school_administrators'] as $administrator) {
                        MessageCompanyAdministrator::updateOrCreate(
                            ['school_administrator_id' => $administrator['id']],
                            [
                                'message_school_id' => $messageCompany->id
                            ]
                        );
                    }


                    // Delete MessageCompanyAdministrator records where school_administrator_id is not in the provided request
                    MessageCompanyAdministrator::where('message_school_id', $messageCompany->id)
                        ->whereNotIn('school_administrator_id', collect($school['school_administrators'])->pluck('id')->all())
                        ->delete();


                    foreach ($school['students'] as $student) {

                        $messageCompanyCompany = MessageCompanyCompany::updateOrCreate(
                            [
                                'message_school_id' => $messageCompany->id,
                                'academic_id' => $student['academic_id']
                            ],
                            [
                                'student_status' => 'active',
                                'student_sponsor_status' => 'pending'
                            ]
                        );


                        foreach ($student['combination_classes'] as $combinationClass) {
                            MessageCompanyCombinationClass::updateOrCreate(
                                [
                                    'school_combination_class_id' => $combinationClass['school_combination_class_id'],
                                    'message_school_id' => $messageCompany->id
                                ],
                                []
                            );
                        }


                        // Delete MessageCompanyCombinationClass records where combination class IDs are not in the provided request
                        MessageCompanyCombinationClass::where('message_school_id', $messageCompany->id)
                            ->whereNotIn('school_combination_class_id', collect($student['combination_classes'])->pluck('school_combination_class_id')->all())
                            ->delete();



                        foreach ($student['recepient_types'] as $recepientType) {


                            $studentRecepientType = CompanyRecepientType::where('id', $recepientType['id'])->first();

                            if ($studentRecepientType) {

                                $messageCompanyCompanyRecepientType = MessageCompanyCompanyRecepientType::updateOrCreate(
                                    [
                                        'message_school_student_id' => $messageCompanyCompany->id,
                                        'student_recepient_type_id' => $studentRecepientType->id
                                    ]
                                );


                                // Delete MessageCompanyCompanyRecepientType records where student_recepient_type_id is not in the provided request
                                MessageCompanyCompanyRecepientType::where('message_school_student_id', $messageCompanyCompany->id)
                                    ->whereNotIn('student_recepient_type_id', collect($student['recepient_types'])->pluck('id')->all())
                                    ->delete();

                                $students = Company::whereNotNull($studentRecepientType->key)->get();

                                foreach ($students as $student) {
                                    if (isset($student->{$studentRecepientType->key})) {

                                        $phone = $student->{$studentRecepientType->key};

                                        // check if json
                                        $json = is_array($phone) ? $phone : json_decode($phone, true);

                                        if ($json) {

                                            $code = trim(preg_replace('/\s+/', '', $json['code']));
                                            $number = trim(preg_replace('/\s+/', '', $json['number']));

                                            // check if code exists in country table
                                            $country = Country::where('phone_code', $code)->first();

                                            if ($country) {
                                                // check id number is integer
                                                if (is_int((int)$number)) {

                                                    MessageCompanyCompanyRecepient::updateOrCreate(
                                                        [
                                                            'message_school_student_recepient_type_id' => $messageCompanyCompanyRecepientType->id,
                                                            'student_id' => $student->id
                                                        ],
                                                        [
                                                            'phone' => [
                                                                'code' => $code,
                                                                'number' => $number
                                                            ]
                                                        ]
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }



                        // Delete MessageCompanyCompany records where student ID is not in the provided request
                        MessageCompanyCompany::where('message_school_id', $messageCompany->id)
                            ->whereNotIn('academic_id', collect($school['students'])->pluck('academic_id')->all())
                            ->delete();
                    }
                }


                // Delete MessageUser records where user IDs are not present in the provided request
                MessageUser::where('message_id', $message->id)
                    ->whereNotIn('user_id', collect($recipient['users'])->pluck('id')->all())
                    ->delete();

                // Delete MessageCompany records not present in the provided request
                MessageCompany::where('message_id', $message->id)
                    ->whereNotIn('school_id', collect($recipient['schools'])->pluck('school_id')->all())
                    ->delete();
            }

            DB::commit();

            return $this->successResponse('Message updated successfully', $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
