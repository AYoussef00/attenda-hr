<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Department;
use App\Models\Company\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $departments = Department::where('company_id', $company->id)
            ->withCount('employees')
            ->latest('created_at')
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'employees_count' => $department->employees_count,
                    'created_at' => $department->created_at->format('Y-m-d'),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Company/Departments/Index', [
            'departments' => $departments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Company/Departments/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Department::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
        ]);

        return redirect()->route('company.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $department = Department::where('company_id', $company->id)
            ->findOrFail($id);

        // Check if department has employees
        if ($department->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete department with employees. Please reassign or remove employees first.');
        }

        $department->delete();

        return redirect()->route('company.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
