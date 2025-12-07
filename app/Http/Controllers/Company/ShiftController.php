<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Shift;
use App\Models\Company\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
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

        $shifts = Shift::where('company_id', $company->id)
            ->withCount('employees')
            ->latest('created_at')
            ->get()
            ->map(function ($shift) {
                $startTime = \Carbon\Carbon::parse($shift->start_time);
                $endTime = \Carbon\Carbon::parse($shift->end_time);
                $totalMinutes = $startTime->diffInMinutes($endTime);
                $totalHours = ($totalMinutes - $shift->break_minutes) / 60;

                return [
                    'id' => $shift->id,
                    'name' => $shift->name,
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $endTime->format('H:i'),
                    'break_minutes' => $shift->break_minutes,
                    'late_grace_minutes' => $shift->late_grace_minutes,
                    'overtime_after' => $shift->overtime_after,
                    'total_hours' => round($totalHours, 2),
                    'employees_count' => $shift->employees_count,
                    'created_at' => $shift->created_at->format('Y-m-d'),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Company/Shifts/Index', [
            'shifts' => $shifts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Company/Shifts/Create');
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
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
            'late_grace_minutes' => ['nullable', 'integer', 'min:0'],
            'overtime_after' => ['nullable', 'integer', 'min:0'],
        ]);

        Shift::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'break_minutes' => $validated['break_minutes'] ?? 0,
            'late_grace_minutes' => $validated['late_grace_minutes'] ?? 0,
            'overtime_after' => $validated['overtime_after'] ?? null,
        ]);

        return redirect()->route('company.shifts.index')
            ->with('success', 'Shift created successfully.');
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

        $shift = Shift::where('company_id', $company->id)
            ->findOrFail($id);

        // Check if shift has employees
        if ($shift->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete shift with employees. Please reassign or remove employees first.');
        }

        $shift->delete();

        return redirect()->route('company.shifts.index')
            ->with('success', 'Shift deleted successfully.');
    }
}
