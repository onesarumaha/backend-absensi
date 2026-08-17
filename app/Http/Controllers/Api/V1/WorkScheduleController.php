<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkSchedule\StoreWorkScheduleRequest;
use App\Http\Requests\WorkSchedule\UpdateWorkScheduleRequest;
use App\Http\Resources\WorkScheduleResource;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $schedules = WorkSchedule::query()
            ->latest()
            ->paginate(10);

        return WorkScheduleResource::collection($schedules);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWorkScheduleRequest $request)
    {
        $schedule = WorkSchedule::create(
            $request->validated()
        );

        return new WorkScheduleResource($schedule);
    }


    /**
     * Display the specified resource.
     */
    public function show(WorkSchedule $workSchedule)
    {
        return new WorkScheduleResource($workSchedule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkScheduleRequest $request,WorkSchedule $workSchedule) 
    {
        $workSchedule->update(
            $request->validated()
        );

        return new WorkScheduleResource($workSchedule);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkSchedule $workSchedule)
    {
        if ($workSchedule->employees()->exists()) {
            return response()->json([
                'message' => 'Work schedule tidak dapat dihapus karena masih digunakan oleh pegawai.',
            ], 422);
        }

        $workSchedule->delete();

        return response()->json([
            'message' => 'Work schedule berhasil dihapus.',
        ]);
    }
}
