<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\v1\StoreEmployeeRequest;
use App\Http\Requests\api\v1\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::with([
            'user',
            'department',
            'position',
            'workSchedule',
        ])
            ->latest()
            ->paginate(15);

        return EmployeeResource::collection($employees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request)
    {
        $employee = DB::transaction(function () use ($request) {

            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make(
                    $request->password
                ),
                'role' => 'pegawai',
            ]);

            return Employee::create([
                'user_id' => $user->id,
                'employee_number' => $request->employee_number,
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'address' => $request->address,
                'department_id' => $request->department_id,
                'position_id' => $request->position_id,
                'work_schedule_id' => $request->work_schedule_id,
                'join_date' => $request->join_date,
                'status' => $request->status ?? 'active',
            ]);
        });

        $employee->load([
            'user',
            'department',
            'position',
            'workSchedule',
        ]);

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode(201);

    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $employee->load([
            'user',
            'department',
            'position',
            'workSchedule',
        ]);

        return new EmployeeResource($employee);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        DB::transaction(function () use ( $request, $employee ) {
            $user = $employee->user;
            $userData = [];

            if ($request->has('email')) {
                $userData['email'] = $request->email;
            }

            if ($request->filled('password')) {
                $userData['password'] = Hash::make(
                    $request->password
                );
            }

            if (!empty($userData)) {
                $user->update($userData);
            }


            $employee->update(
                $request->safe()->only([
                    'employee_number',
                    'full_name',
                    'phone',
                    'address',
                    'department_id',
                    'position_id',
                    'work_schedule_id',
                    'join_date',
                    'status',
                ])
            );
        });

        $employee->load([
            'user',
            'department',
            'position',
            'workSchedule',
        ]);

        return new EmployeeResource($employee);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
         DB::transaction(function () use ($employee) {
            $employee->delete();
        });

        return response()->json([
            'message' => 'Pegawai berhasil dihapus.',
        ]);
    }
}
