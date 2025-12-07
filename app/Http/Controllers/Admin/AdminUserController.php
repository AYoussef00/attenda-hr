<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index(Request $request)
    {
        // All users except pure employees are considered "admin users" for this screen
        $users = User::where('role', '!=', 'employee')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'last_login' => optional($user->last_login)->toDateTimeString(),
                    'created_at' => optional($user->created_at)->toDateTimeString(),
                ];
            });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:super_admin,company_admin,hr,manager,user'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        User::create([
            'company_id' => null, // System-level admin
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => null,
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user created successfully.');
    }
}


