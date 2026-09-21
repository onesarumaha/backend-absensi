<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

        Route::post('/login', [ AuthController::class,'login', ]);
    
        Route::middleware('auth:sanctum')->group(function () { 
            Route::get('/me', [ AuthController::class, 'me',]);
            Route::post('/me/photo', [AuthController::class, 'uploadPhoto']);
            Route::post('/logout', [AuthController::class,'logout', ]);

            Route::middleware('role:admin')->group(function () {
                Route::apiResource('employees', EmployeeController::class);
                Route::apiResource('departments', DepartmentController::class);
                Route::apiResource('positions', PositionController::class);
                Route::apiResource('work-schedules', WorkScheduleController::class);
            });

            Route::prefix('attendance')->group(function () {
                Route::get('/today', [ AttendanceController::class,'today', ]);
                Route::get('/history', [AttendanceController::class,  'history', ]);
                Route::get('/schedule-info', [AttendanceController::class, 'scheduleInfo']); 
                Route::post('/check-in', [ AttendanceController::class,'checkIn', ]);
                Route::post('/check-out', [ AttendanceController::class, 'checkOut',]);
                Route::get('/monthly-recap', [AttendanceController::class, 'monthlyRecap']);

            });

            Route::middleware('role:admin')->group(function () {
                Route::get('/attendances', [ AttendanceController::class, 'index',]);
                Route::get('/attendances/{attendance}', [ AttendanceController::class,  'show',  ]);
             });

            Route::prefix('my')->group(function () {
                Route::get('/leave-requests', [LeaveRequestController::class, 'myRequests']);
                Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
                Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show']);
                Route::put('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'update']);   // ← TAMBAH
                Route::delete('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'destroy']);
            });

            Route::middleware('role:admin')->group(function () {

                Route::get('/leave-requests', [LeaveRequestController::class,'index',]);
                Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show', ]);
                Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
                Route::post('/leave-requests/{leaveRequest}/reject',[LeaveRequestController::class, 'reject']);
            });

        });

});
