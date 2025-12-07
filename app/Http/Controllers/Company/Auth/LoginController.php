<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    /**
     * Show the company login form.
     */
    public function showLoginForm(Request $request)
    {
        return Inertia::render('Company/Auth/Login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Check if user is a company admin (not super admin)
            if ($user->isSuperAdmin()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Access denied. Please use the admin login.',
                ])->onlyInput('email');
            }

            // Check if user has a company
            if (!$user->company_id) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'User does not belong to any company.',
                ])->onlyInput('email');
            }

            $company = $user->company;

            // Check if user is active
            if (!$user->isActive()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is inactive. Please contact support.',
                ])->onlyInput('email');
            }

            // Check if user is an employee and has an employee record
            if ($user->isEmployee()) {
                $employee = Employee::where('user_id', $user->id)
                    ->where('company_id', $company->id)
                    ->first();

                if (!$employee) {
                    Auth::logout();
                    return back()->withErrors([
                        'email' => 'Employee record not found. Please contact your company.',
                    ])->onlyInput('email');
                }
            }

            // Update last login
            $user->update(['last_login' => now()]);

            $request->session()->regenerate();

            // Redirect based on user role
            if ($user->isCompanyAdmin() || $user->isHr() || $user->isManager()) {
                return redirect()->intended(route('company.dashboard'));
            } elseif ($user->isEmployee()) {
                return redirect()->intended(route('employee.dashboard'));
            }

            // Fallback to company dashboard
            return redirect()->intended(route('company.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}
