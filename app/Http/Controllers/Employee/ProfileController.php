<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Display the employee profile.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->with(['department', 'shift', 'user', 'company'])
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        return Inertia::render('Employee/Profile/Index', [
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'position' => $employee->position,
                'hire_date' => $employee->hire_date?->format('Y-m-d'),
                'hire_date_formatted' => $employee->hire_date?->format('F d, Y'),
                'contract_type' => $employee->contract_type,
                'status' => $employee->status,
                'department' => $employee->department ? [
                    'id' => $employee->department->id,
                    'name' => $employee->department->name,
                ] : null,
                'shift' => $employee->shift ? [
                    'id' => $employee->shift->id,
                    'name' => $employee->shift->name,
                    'start_time' => $employee->shift->start_time,
                    'end_time' => $employee->shift->end_time,
                ] : null,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'last_login' => $user->last_login?->format('Y-m-d H:i:s'),
                'last_login_formatted' => $user->last_login?->diffForHumans(),
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'logo' => $company->logo ? asset('storage/' . $company->logo) : null,
            ],
        ]);
    }

    /**
     * Update the employee profile information.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Update user information
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? $user->phone;

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('employee.profile.index')
            ->with('success', 'Profile updated successfully.');
    }
}
