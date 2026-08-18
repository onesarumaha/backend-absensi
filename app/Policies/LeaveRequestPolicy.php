<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaveRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
       return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole('admin') || $leaveRequest->employee_id === $user->employee_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
       return $user->employee_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole('admin')
            || (
                $leaveRequest->employee_id === $user->employee_id
                && $leaveRequest->status === 'pending'
            );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole('admin')
            || (
                $leaveRequest->employee_id === $user->employee_id
                && $leaveRequest->status === 'pending'
            );
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole('admin')
            && $leaveRequest->status === 'pending';
    }

    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole('admin')
            && $leaveRequest->status === 'pending';
    }
}
