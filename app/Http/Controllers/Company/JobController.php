<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Job;
use App\Models\Company\Candidate;
use App\Services\CVExtractorService;
use App\Services\JobMatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class JobController extends Controller
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

        $jobs = Job::latest('created_at')
            ->withCount('candidates')
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'description' => $job->description,
                    'skills' => $job->skills,
                    'candidates_count' => $job->candidates_count,
                    'created_at' => $job->created_at->format('Y-m-d'),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Company/Jobs/Index', [
            'jobs' => $jobs,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Company/Jobs/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'skills' => ['required', 'array'],
            'skills.*' => ['string'],
            'auto_reject_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_reject_max' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_accept_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_accept_max' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        Job::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'skills' => $validated['skills'],
            'auto_reject_min' => $validated['auto_reject_min'] ?? null,
            'auto_reject_max' => $validated['auto_reject_max'] ?? null,
            'auto_accept_min' => $validated['auto_accept_min'] ?? null,
            'auto_accept_max' => $validated['auto_accept_max'] ?? null,
        ]);

        return redirect()->route('company.jobs.index')
            ->with('success', 'Job created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $job = Job::withCount('candidates')
            ->with(['candidates' => function ($query) {
                $query->latest('created_at');
            }])
            ->findOrFail($id);

        $matchService = new JobMatchService();

        // Get candidates grouped by status
        $acceptedCandidates = $job->candidates()
            ->where('status', 'accepted')
            ->orderBy('match_percentage', 'desc')
            ->get();

        $pendingCandidates = $job->candidates()
            ->where('status', 'pending')
            ->orderBy('match_percentage', 'desc')
            ->get();

        $rejectedCandidates = $job->candidates()
            ->where('status', 'rejected')
            ->orderBy('match_percentage', 'desc')
            ->get();

        $mapCandidate = function ($candidate) use ($job, $matchService) {
                    // Get CV text for analysis (simplified - we'll use stored data)
                    $cvText = '';

                    // Analyze candidate (we can analyze without full CV text since we have extracted data)
                    $analysis = $matchService->analyzeCandidate(
                        $job->skills ?? [],
                        $job->title,
                        $candidate->skills ?? [],
                        $candidate->title,
                        $candidate->experience_years,
                        $cvText
                    );

                    return [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'email' => $candidate->email,
                        'phone' => $candidate->phone,
                        'title' => $candidate->title,
                        'skills' => $candidate->skills,
                        'experience_years' => $candidate->experience_years,
                        'match_percentage' => $candidate->match_percentage,
                        'resume_path' => $candidate->resume_path,
                        'status' => $candidate->status ?? 'pending',
                        'created_at' => $candidate->created_at->format('Y-m-d H:i:s'),
                        'strengths' => $analysis['strengths'],
                        'weaknesses' => $analysis['weaknesses'],
                    ];
                };

        return Inertia::render('Company/Jobs/Show', [
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'skills' => $job->skills,
                'auto_reject_min' => $job->auto_reject_min,
                'auto_reject_max' => $job->auto_reject_max,
                'auto_accept_min' => $job->auto_accept_min,
                'auto_accept_max' => $job->auto_accept_max,
                'candidates_count' => $job->candidates_count,
                'created_at' => $job->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $job->updated_at->format('Y-m-d H:i:s'),
                'accepted_candidates' => $acceptedCandidates->map($mapCandidate)->toArray(),
                'pending_candidates' => $pendingCandidates->map($mapCandidate)->toArray(),
                'rejected_candidates' => $rejectedCandidates->map($mapCandidate)->toArray(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $job = Job::findOrFail($id);

        return Inertia::render('Company/Jobs/Edit', [
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'skills' => $job->skills,
                'auto_reject_min' => $job->auto_reject_min,
                'auto_reject_max' => $job->auto_reject_max,
                'auto_accept_min' => $job->auto_accept_min,
                'auto_accept_max' => $job->auto_accept_max,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'skills' => ['required', 'array'],
            'skills.*' => ['string'],
            'auto_reject_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_reject_max' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_accept_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_accept_max' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $job->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'skills' => $validated['skills'],
            'auto_reject_min' => $validated['auto_reject_min'] ?? null,
            'auto_reject_max' => $validated['auto_reject_max'] ?? null,
            'auto_accept_min' => $validated['auto_accept_min'] ?? null,
            'auto_accept_max' => $validated['auto_accept_max'] ?? null,
        ]);

        return redirect()->route('company.jobs.index')
            ->with('success', 'Job updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $job = Job::findOrFail($id);

        // Delete associated candidates (cascade will handle this, but we can be explicit)
        $job->candidates()->delete();

        $job->delete();

        return redirect()->route('company.jobs.index')
            ->with('success', 'Job deleted successfully.');
    }

    /**
     * Upload CV files for candidates
     */
    public function uploadCandidates(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        $validated = $request->validate([
            'resumes' => ['required', 'array', 'min:1'],
            'resumes.*' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB max
        ]);

        $extractor = new CVExtractorService();
        $matchService = new JobMatchService();
        $createdCandidates = [];
        $errors = [];

        // Get CV text for advanced matching using reflection to access private method
        $cvTexts = [];
        foreach ($validated['resumes'] as $index => $resumeFile) {
            try {
                $extension = strtolower($resumeFile->getClientOriginalExtension());
                if ($extension === 'pdf') {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($resumeFile->getRealPath());
                    $cvTexts[$index] = $pdf->getText();
                } elseif (in_array($extension, ['doc', 'docx'])) {
                    // Use CVExtractorService's method via reflection
                    $reflection = new \ReflectionClass($extractor);
                    $method = $reflection->getMethod('extractFromWord');
                    $method->setAccessible(true);
                    $cvTexts[$index] = $method->invoke($extractor, $resumeFile);
                }
            } catch (\Exception $e) {
                $cvTexts[$index] = '';
            }
        }

        foreach ($validated['resumes'] as $index => $resumeFile) {
            try {
                // Extract data from CV
                $extractedData = $extractor->extractData($resumeFile);

                // Store the resume file
                $resumePath = $resumeFile->store('candidates/resumes', 'public');

                // Calculate advanced match percentage
                $jobSkills = $job->skills ?? [];
                $candidateSkills = $extractedData['skills'] ?? [];
                $candidateTitle = $extractedData['title'] ?? null;
                $candidateExperienceYears = $extractedData['experience_years'] ?? null;
                $cvText = $cvTexts[$index] ?? '';

                $matchPercentage = $matchService->calculateMatchPercentage(
                    $jobSkills,
                    $job->title,
                    $candidateSkills,
                    $candidateTitle,
                    $candidateExperienceYears,
                    $cvText
                );

                // Use extracted data or fallback to defaults
                $name = $extractedData['name'] ?? 'Unknown Candidate';
                $email = $extractedData['email'] ?? null;
                $phone = $extractedData['phone'] ?? 'N/A';
                $title = $extractedData['title'] ?? 'Not Specified';
                $experienceYears = $extractedData['experience_years'] ?? null;

                // Create candidate
                $candidate = Candidate::create([
                    'job_id' => $job->id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'title' => $title,
                    'skills' => $candidateSkills,
                    'experience_years' => $experienceYears,
                'resume_path' => $resumePath,
                'match_percentage' => $matchPercentage,
                'status' => $this->determineAutoStatus($job, $matchPercentage),
            ]);

                $createdCandidates[] = $candidate;
            } catch (\Exception $e) {
                $errors[] = "Error processing file " . ($index + 1) . ": " . $e->getMessage();
                Log::error('CV Upload Error: ' . $e->getMessage());
            }
        }

        $message = count($createdCandidates) . ' candidate(s) uploaded successfully.';
        if (!empty($errors)) {
            $message .= ' Errors: ' . implode(', ', $errors);
        }

        return back()->with('success', $message);
    }

    /**
     * Delete a candidate
     */
    public function destroyCandidate(Request $request, string $jobId, string $candidateId)
    {
        $job = Job::findOrFail($jobId);
        $candidate = Candidate::where('job_id', $job->id)
            ->findOrFail($candidateId);

        // Delete the resume file if exists
        if ($candidate->resume_path && Storage::disk('public')->exists($candidate->resume_path)) {
            Storage::disk('public')->delete($candidate->resume_path);
        }

        $candidate->delete();

        return back()->with('success', 'Candidate deleted successfully.');
    }

    /**
     * Update candidate status
     */
    public function updateCandidateStatus(Request $request, string $jobId, string $candidateId)
    {
        $job = Job::findOrFail($jobId);
        $candidate = Candidate::where('job_id', $job->id)
            ->findOrFail($candidateId);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,accepted,rejected'],
        ]);

        $candidate->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Candidate status updated successfully.');
    }

    /**
     * Update job auto status settings only
     */
    public function updateSettings(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        $validated = $request->validate([
            'auto_reject_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_reject_max' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_accept_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_accept_max' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $job->update([
            'auto_reject_min' => $validated['auto_reject_min'] ?? null,
            'auto_reject_max' => $validated['auto_reject_max'] ?? null,
            'auto_accept_min' => $validated['auto_accept_min'] ?? null,
            'auto_accept_max' => $validated['auto_accept_max'] ?? null,
        ]);

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Determine candidate status based on auto settings
     */
    private function determineAutoStatus(Job $job, int $matchPercentage): string
    {
        // Check auto reject range
        if ($job->auto_reject_min !== null && $job->auto_reject_max !== null) {
            if ($matchPercentage >= $job->auto_reject_min && $matchPercentage <= $job->auto_reject_max) {
                return 'rejected';
            }
        }

        // Check auto accept range
        if ($job->auto_accept_min !== null && $job->auto_accept_max !== null) {
            if ($matchPercentage >= $job->auto_accept_min && $matchPercentage <= $job->auto_accept_max) {
                return 'accepted';
            }
        }

        // Default to pending if no auto rules match
        return 'pending';
    }

}
