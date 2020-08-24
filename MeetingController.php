<?php

declare(strict_types=1);

namespace Modules\Meeting\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Modules\Http\Controllers\ApiController;
use Modules\Meeting\Http\Resources\MeetingCollection;
use Modules\Meeting\Http\Resources\MeetingResource;
use Modules\Meeting\Models\Meeting;
use Modules\Meeting\Services\MeetingService;
use Modules\Meeting\Validators\MeetingStoreRequest;
use Modules\Meeting\Validators\MeetingUpdateRequest;
use Modules\Meeting\Services\OpentokService;

class MeetingController extends ApiController
{

    private $meetingService;

    /**
     * Create a new controller instance.
     *
     * @param MeetingService $meetingService
     */
    public function __construct(MeetingService $meetingService)
    {

        //$this->middleware('api');
        $this->meetingService = $meetingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {

        try {

            $limit = (int)(request('limit') ?? 20);
            $data = $this->meetingService->paginate($limit);

            return $this->sendPaginate(new MeetingCollection($data));

        } catch (Exception $exception) {

            return $this->sendError('Server Error.', $exception);

        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {

        try {

            $data = $this->meetingService->all();

            return $this->sendResource(MeetingResource::collection($data));

        } catch (Exception $exception) {

            return $this->sendError('Server Error.', $exception);
        }
    }

    /**
     * Display a listing of choices.
     *
     * @return JsonResponse
     */
    public function listOfChoices(): JsonResponse
    {

        try {

            $data = $this->meetingService->listOfChoices();

            return $this->sendSimpleJson($data);

        } catch (Exception $exception) {

            return $this->sendError('Server Error.', $exception);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store()
    {

        try {

            $storeRequest = new MeetingStoreRequest();
            $validator = Validator::make(request()->all(), $storeRequest->rules(), $storeRequest->messages());

            if ($validator->fails()) {

                return $this->sendBadRequest('Validation Error.', $validator->errors()->toArray());
            }

            $item = $this->meetingService->create(request()->all());

            return $this->sendResponse($item->toArray());

        } catch (Exception $exception) {

            return $this->sendError('Server Error.', $exception);

        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Meeting $meeting
     * @return JsonResponse
     */
    public function update(Meeting $meeting)
    {
        
        try {

            $updateRequest = new MeetingUpdateRequest();
            $validator = Validator::make(request()->all(), $updateRequest->rules(), $updateRequest->messages());

            if ($validator->fails()) {

                return $this->sendBadRequest('Validation Error.', $validator->errors()->toArray());
            }

            $item = $this->meetingService->update(request()->all(), $meeting);

            return $this->sendResponse($item->toArray());

        } catch (Exception $exception) {

            return $this->sendError('Server Error.', $exception);

        }
    }

    /**
     * Join a participating into a meeting.
     *
     * @param Meeting $meeting
     * @return JsonResponse
     */
    public function join(Meeting $meeting)
    {
        try {

            if($meeting->date > now()){
                
                return $this->sendBadRequest('This Meeting is not available yet.', ['error' => 'NOT_AVAILABLE']);
            }

            if(!$meeting->session_id || $meeting->session_id == "" | empty($meeting->session_id)) {
                $opentokService = new OpentokService();
                $keys = $opentokService->create();

                
                $keysToSave['session_id'] = $keys["session_id"];
                $keysToSave['token'] = $keys["session_token"];
            
                $meeting = $opentokService->update($keysToSave, $meeting);
                
            } 
            $meeting->api_key = '46884074';
            return $this->sendResponse($meeting->toArray());            

        } catch (Exception $exception) {

            return $this->sendError('Server Error.', $exception);

        }
    }

    /**
     * Display the specified resource.
     *
     * @param Meeting $meeting
     * @return JsonResponse
     */
    public function show(Meeting $meeting): JsonResponse
    {

        try {

            return $this->sendResource(new MeetingResource($meeting));

        } catch (Exception $exception) {

            return $this->sendError('Server Error.', $exception);

        }
    }

    /**
     * Display the specified resource.
     *
     * @param Meeting $meeting
     * @return JsonResponse
     */
    public function destroy(Meeting $meeting): JsonResponse
    {

        try {

            $item = $this->meetingService->delete($meeting);

            return $this->sendResource(new MeetingResource($item));

        } catch (Exception $exception) {

            return $this->sendError('Server Error.', $exception);

        }
    }
}
