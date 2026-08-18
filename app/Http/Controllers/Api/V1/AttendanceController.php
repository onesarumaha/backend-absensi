<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        $attendances = Attendance::query()
            ->with('employee')
            ->when(
                $request->filled('employee_id'), fn ($query) =>
                    $query->where(
                        'employee_id',
                        $request->employee_id
                    )
            )
            ->when(
                $request->filled('status'),
                fn ($query) =>
                    $query->where(
                        'status',
                        $request->status
                    )
            )
            ->when(
                $request->filled('date'),
                fn ($query) =>
                    $query->whereDate(
                        'date',
                        $request->date
                    )
            )
            ->latest('date')
            ->paginate($request->integer('per_page', 10));

        return AttendanceResource::collection($attendances);
    }
    
    public function checkIn(CheckInRequest $request)
    {
        $user = $request->user();

        $employee = $user->employee;

        $today = now()->toDateString();

        $now = now();

        $lateMinutes = 0;
        $status = 'hadir';

        if ($employee->workSchedule) {

            $scheduleStart = Carbon::parse(
                $today . ' ' . $employee->workSchedule->start_time
            );

            if ($now->greaterThan($scheduleStart)) {
                $lateMinutes = $scheduleStart->diffInMinutes($now);
                $status = 'terlambat';
            }
        }

        $attendance = DB::transaction(function () use (
            $employee,
            $today,
            $now,
            $status,
            $lateMinutes,
            $request
        ) {
            return Attendance::create([
                'employee_id' => $employee->id,

                'date' => $today,

                'check_in' => $now->format('H:i:s'),

                'status' => $status,

                'late_minutes' => $lateMinutes,

                'check_in_latitude' =>
                    $request->latitude,

                'check_in_longitude' =>
                    $request->longitude,

                'notes' =>
                    $request->notes,
            ]);
        });

        $attendance->load('employee');

        return new AttendanceResource($attendance);
    }

    public function checkOut(CheckOutRequest $request)
    {
        $user = $request->user();

        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'User belum terhubung dengan data pegawai.',
            ], 422);
        }

        $attendance = Attendance::where(
            'employee_id',
            $employee->id
        )
            ->whereDate('date', now()->toDateString())
            ->first();

        if (! $attendance) {
            return response()->json([
                'message' => 'Anda belum melakukan check-in hari ini.',
            ], 422);
        }

        if (! $attendance->check_in) {
            return response()->json([
                'message' => 'Anda belum melakukan check-in hari ini.',
            ], 422);
        }

        if ($attendance->check_out) {
            return response()->json([
                'message' => 'Anda sudah melakukan check-out hari ini.',
            ], 422);
        }

        $attendance->update([
            'check_out' => now()->format('H:i:s'),

            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,

            'notes' => $request->filled('notes')
                ? $request->notes
                : $attendance->notes,
        ]);

        $attendance->load('employee');

        return new AttendanceResource($attendance);
    }

    public function today(Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'User belum terhubung dengan data pegawai.',
            ], 422);
        }

        $attendance = Attendance::with('employee')
            ->where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if (! $attendance) {
            return response()->json([
                'data' => null,
                'message' => 'Belum ada absensi hari ini.',
            ]);
        }

        return new AttendanceResource($attendance);
    }

    public function history(Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'User belum terhubung dengan data pegawai.',
            ], 422);
        }

        $attendances = Attendance::with('employee')
            ->where('employee_id', $employee->id)
            ->latest('date')
            ->paginate(10);

        return AttendanceResource::collection($attendances);
    }

    public function show(Attendance $attendance)
    {
        $this->authorize('view', $attendance);

        $attendance->load('employee');

        return new AttendanceResource($attendance);
    }
}
