<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\DemoRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        
        $query = DemoRequest::with('handledBy')
            ->latest('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->get()
            ->map(function ($demoRequest) {
                return [
                    'id' => $demoRequest->id,
                    'first_name' => $demoRequest->first_name,
                    'business_email' => $demoRequest->business_email,
                    'company_name' => $demoRequest->company_name,
                    'phone_number' => $demoRequest->phone_number,
                    'number_of_employees' => $demoRequest->number_of_employees,
                    'company_headquarters' => $demoRequest->company_headquarters,
                    'choose_time_slot' => $demoRequest->choose_time_slot,
                    'status' => $demoRequest->status,
                    'notes' => $demoRequest->notes,
                    'handled_by_name' => $demoRequest->handledBy->name ?? null,
                    'contacted_at' => $demoRequest->contacted_at ? $demoRequest->contacted_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $demoRequest->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Admin/Requests/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'business_email' => ['required', 'email', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255'],
            'number_of_employees' => ['required', 'string', 'max:255'],
            'company_headquarters' => ['required', 'string', 'max:255'],
            'choose_time_slot' => ['required', 'in:Yes,No'],
        ]);

        DemoRequest::create($validated);

        return back()->with('success', 'Demo request submitted successfully. We will contact you soon!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $demoRequest = DemoRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,contacted,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'contacted' && !$demoRequest->contacted_at) {
            $validated['contacted_at'] = now();
            $validated['handled_by'] = $request->user()->id;
        }

        $demoRequest->update($validated);

        return back()->with('success', 'Request updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $demoRequest = DemoRequest::findOrFail($id);
        $demoRequest->delete();

        return back()->with('success', 'Request deleted successfully.');
    }
}
