<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Models\Company\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeAuthController extends ApiController
{
    /**
     * Login endpoint for mobile apps (Android / iOS).
     *
     * Expects email + password, returns a bearer token and basic employee info.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->isEmployee() || ! $user->isActive() || ! $user->company) {
            return $this->error('User is not an active employee.', 403);
        }

        /** @var Employee|null $employee */
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->with(['department', 'shift'])
            ->first();

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee record not found or inactive.', 403);
        }

        $deviceName = $validated['device_name'] ?? 'mobile';

        // Revoke existing tokens for this device name (optional cleanup)
        $user->tokens()
            ->where('name', $deviceName)
            ->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'company_id' => $user->company_id,
            ],
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'status' => $employee->status,
                'position' => $employee->position,
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
                'hire_date' => $employee->hire_date?->format('Y-m-d'),
                'contract_type' => $employee->contract_type,
            ],
        ], 'Login successful.');
    }

    /**
     * Logout endpoint - revokes the current access token.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Return authenticated user + employee details using the current token.
     */
    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user || ! $user->company || ! $user->isEmployee()) {
            return $this->error('User is not an employee.', 403);
        }

        /** @var Employee|null $employee */
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->first();

        if (! $employee) {
            return $this->error('Employee record not found.', 404);
        }

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'company_id' => $user->company_id,
            ],
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'status' => $employee->status,
                'position' => $employee->position,
            ],
        ]);
    }
}


