<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequest\ProcessLeaveRequestRequest;
use App\Http\Requests\LeaveRequest\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     public function index(Request $request)
    {
        $leaveRequests = LeaveRequest::query()
            ->with([
                'employee',
                'approvedBy',
            ])
            ->when(
                $request->filled('status'),
                fn ($query) =>
                    $query->where(
                        'status',
                        $request->status
                    )
            )
            ->when(
                $request->filled('type'),
                fn ($query) =>
                    $query->where(
                        'type',
                        $request->type
                    )
            )
            ->when(
                $request->filled('employee_id'),
                fn ($query) =>
                    $query->where(
                        'employee_id',
                        $request->employee_id
                    )
            )
            ->latest()
            ->paginate(
                $request->integer('per_page', 10)
            );

        return LeaveRequestResource::collection(
            $leaveRequests
        );
    }

    public function myRequests(Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'User belum terhubung dengan data pegawai.',
            ], 422);
        }

        $leaveRequests = LeaveRequest::with([
            'employee',
            'approvedBy',
        ])
            ->where('employee_id', $employee->id)
            ->latest()
            ->paginate(10);

        return LeaveRequestResource::collection(
            $leaveRequests
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeaveRequestRequest $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'User belum terhubung dengan data pegawai.',
            ], 422);
        }

        $overlap = LeaveRequest::where(
            'employee_id',
            $employee->id
        )
            ->whereIn('status', [
                'pending',
                'approved',
            ])
            ->whereDate(
                'start_date',
                '<=',
                $request->end_date
            )
            ->whereDate(
                'end_date',
                '>=',
                $request->start_date
            )
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' =>
                    'Tanggal pengajuan bertabrakan dengan pengajuan sebelumnya.',
            ], 422);
        }

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,

            'type' => $request->type,

            'start_date' => $request->start_date,

            'end_date' => $request->end_date,

            'reason' => $request->reason,

            'status' => 'pending',
        ]);

        $leaveRequest->load([
            'employee',
            'approvedBy',
        ]);

        return new LeaveRequestResource(
            $leaveRequest
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('show', $leaveRequest);
        $user = $request->user();

      

        if (
            $user->isPegawai() &&
            $leaveRequest->employee_id !==
                $user->employee?->id
        ) {
            return response()->json([
                'message' =>
                    'Anda tidak memiliki akses.',
            ], 403);
        }

        $leaveRequest->load([
            'employee',
            'approvedBy',
        ]);

        return new LeaveRequestResource(
            $leaveRequest
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'User belum terhubung dengan data pegawai.',
            ], 422);
        }

        if ($leaveRequest->employee_id !== $employee->id) {
            return response()->json([
                'message' => 'Anda tidak berhak mengubah pengajuan ini.',
            ], 403);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Pengajuan yang sudah diproses tidak dapat diubah.',
            ], 422);
        }

        $validated = $request->validate([
            'type' => [
                'required',
                \Illuminate\Validation\Rule::in(['izin', 'sakit', 'cuti']),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $overlap = LeaveRequest::where('employee_id', $employee->id)
            ->where('id', '!=', $leaveRequest->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'Tanggal pengajuan bertabrakan dengan pengajuan sebelumnya.',
            ], 422);
        }

        $leaveRequest->update($validated);

        $leaveRequest->load([
            'employee',
            'approvedBy',
        ]);

        return new LeaveRequestResource($leaveRequest);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveRequest $leaveRequest)
    {
        DB::transaction(function () use ($leaveRequest) {
            $leaveRequest->delete();
        });

        return response()->json([
            'message' => 'Cuti berhasil dihapus.',
        ]);
    }

    public function approve( ProcessLeaveRequestRequest $request, LeaveRequest $leaveRequest ) 
    {
        $this->authorize('approve', $leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Pengajuan ini sudah diproses.',
            ], 422);
        }

        DB::transaction(function () use (
            $request,
            $leaveRequest
        ) {

            $this->createAttendanceFromLeave(
                $leaveRequest
            );

            $leaveRequest->update([
                'status' => 'approved',

                'approved_by' =>
                    $request->user()->id,

                'approved_at' => now(),
            ]);
        });

        $leaveRequest->load([
            'employee',
            'approvedBy',
        ]);

        return new LeaveRequestResource(
            $leaveRequest
        );
    }

    /**
     * Admin reject pengajuan.
     */
    public function reject(ProcessLeaveRequestRequest $request, LeaveRequest $leaveRequest) 
    {
        $this->authorize('reject', $leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' =>
                    'Pengajuan ini sudah diproses.',
            ], 422);
        }

        $leaveRequest->update([
            'status' => 'rejected',

            'approved_by' =>
                $request->user()->id,

            'approved_at' => now(),
        ]);

        $leaveRequest->load([
            'employee',
            'approvedBy',
        ]);

        return new LeaveRequestResource(
            $leaveRequest
        );
    }

    /**
     * Membuat attendance otomatis
     * ketika pengajuan disetujui.
     */
    private function createAttendanceFromLeave(
        LeaveRequest $leaveRequest
    ): void {
        $period = CarbonPeriod::create(
            $leaveRequest->start_date,
            $leaveRequest->end_date
        );

        foreach ($period as $date) {
            Attendance::updateOrCreate(
                [
                    'employee_id' =>
                        $leaveRequest->employee_id,

                    'date' =>
                        $date->toDateString(),
                ],
                [
                    'status' =>
                        $leaveRequest->type,

                    'notes' =>
                        $leaveRequest->reason,
                ]
            );
        }
    }
}
