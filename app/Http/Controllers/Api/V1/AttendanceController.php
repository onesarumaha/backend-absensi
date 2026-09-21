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
use Illuminate\Support\Facades\Storage;

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

        $photoPath = $this->saveBase64Photo(
            $request->photo,
            'attendance/check-in',
            'checkin_' . $employee->id . '_' . $now->format('YmdHis')
        );

        $attendance = DB::transaction(function () use (
            $employee, $today, $now, $status, $lateMinutes, $request, $photoPath
        ) {
            return Attendance::create([
                'employee_id' => $employee->id,
                'date' => $today,
                'check_in' => $now->format('H:i:s'),
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'check_in_latitude' => $request->latitude,
                'check_in_longitude' => $request->longitude,
                'check_in_photo' => $photoPath,
                'notes' => $request->notes,
            ]);
        });

        $attendance->load('employee');

        return new AttendanceResource($attendance);
    }

    public function checkOut(CheckOutRequest $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->firstOrFail();

        $now = now();

        $photoPath = $this->saveBase64Photo(
            $request->photo,
            'attendance/check-out',
            'checkout_' . $employee->id . '_' . $now->format('YmdHis')
        );

        $attendance->update([
            'check_out' => $now->format('H:i:s'),
            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,
            'check_out_photo' => $photoPath,
            'notes' => $request->filled('notes') ? $request->notes : $attendance->notes,
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

    private function saveBase64Photo(string $base64, string $folder, string $filename): string
    {
        preg_match('/^data:image\/(\w+);base64,/', $base64, $matches);
        $extension = $matches[1] ?? 'jpg';
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $imageData = base64_decode($imageData);

        // Nama file unik
        $fileName = $filename . '.' . $extension;
        $path = $folder . '/' . $fileName;

        // Simpan ke storage/app/public/
        Storage::disk('public')->put($path, $imageData);

        return $path;
    }

    public function scheduleInfo(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'User belum terhubung dengan data pegawai.',
            ], 422);
        }

        $schedule = $employee->workSchedule;

        return response()->json([
            'success' => true,
            'data' => [
                'work_schedule' => $schedule ? [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'latitude' => $schedule->latitude,
                    'longitude' => $schedule->longitude,
                    'radius_meters' => $schedule->radius_meters ?? 100,
                    'location_name' => $schedule->location_name,
                ] : null,
            ],
        ]);
    }

    /**
     * Rekap absen bulan ini untuk user yang login
     * GET /attendance/monthly-recap
     */
    public function monthlyRecap(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'User belum terhubung dengan data pegawai.',
            ], 422);
        }

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $startOfMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $attendances = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $hadir = $attendances->where('status', 'hadir')->count();
        $terlambat = $attendances->where('status', 'terlambat')->count();
        $izin = $attendances->where('status', 'izin')->count();
        $sakit = $attendances->where('status', 'sakit')->count();
        $cuti = $attendances->where('status', 'cuti')->count();
        $alpha = $attendances->where('status', 'alpha')->count();

        $tahunIni = now()->year;
        $cutiTerpakai = \App\Models\LeaveRequest::where('employee_id', $employee->id)
            ->where('type', 'cuti')
            ->where('status', 'approved')
            ->whereYear('start_date', $tahunIni)
            ->sum(DB::raw('DATEDIFF(end_date, start_date) + 1'));

        $totalCutiTahunan = 12;
        $sisaCuti = max(0, $totalCutiTahunan - $cutiTerpakai);

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'cuti' => $cuti,
                'alpha' => $alpha,
                'sisa_cuti' => $sisaCuti,
                'cuti_terpakai' => $cutiTerpakai,
                'cuti_tahunan' => $totalCutiTahunan,
                'total_hari_kerja' => $attendances->count(),
            ],
        ]);
    }


}
