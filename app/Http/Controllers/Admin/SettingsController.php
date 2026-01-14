<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\PartnerLogo;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display the admin settings page.
     */
    public function index(Request $request)
    {
        // Get saved texts from database
        $text1 = Setting::getValue('settings_text1', 'Finally, a performance management platform that works your way.');
        $text2 = Setting::getValue('settings_text2', 'Bring goals, feedback, and competencies together in one place with a platform that adapts to your process — not the other way around.');

        // Get all partner logos
        $partnerLogos = PartnerLogo::orderBy('display_order')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($logo) {
                // Use asset() helper for public storage URLs
                $logoUrl = $logo->logo_path 
                    ? Storage::disk('public')->url($logo->logo_path)
                    : null;
                
                return [
                    'id' => $logo->id,
                    'logo_url' => $logoUrl,
                    'company_name' => $logo->company_name,
                    'testimonial' => $logo->testimonial,
                    'display_order' => $logo->display_order,
                    'is_active' => $logo->is_active,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Admin/Settings/Index', [
            'text1' => $text1,
            'text2' => $text2,
            'partnerLogos' => $partnerLogos,
        ]);
    }

    /**
     * Update settings texts.
     */
    public function updateTexts(Request $request)
    {
        $validated = $request->validate([
            'text1' => ['required', 'string', 'max:500'],
            'text2' => ['required', 'string', 'max:1000'],
        ]);

        // Store in database
        Setting::setValue('settings_text1', $validated['text1']);
        Setting::setValue('settings_text2', $validated['text2']);

        return back()->with('success', 'Settings texts updated successfully.');
    }

    /**
     * Store new partner logos (supports multiple uploads).
     */
    public function storePartnerLogo(Request $request)
    {
        // Handle both single file and array of files
        $files = $request->has('logos') ? $request->file('logos') : [];
        
        // If logos is not an array, try to get it as single file
        if (empty($files) && $request->hasFile('logo')) {
            $files = [$request->file('logo')];
        }

        $validated = $request->validate([
            'logos' => ['sometimes', 'array'],
            'logos.*' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'logo' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'testimonial' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($files)) {
            return back()->withErrors(['logos' => 'Please select at least one image file.']);
        }

        // Get the highest display order
        $maxOrder = PartnerLogo::max('display_order') ?? 0;
        $uploadedCount = 0;

        // Store each logo
        foreach ($files as $index => $logoFile) {
            if ($logoFile && $logoFile->isValid()) {
                $logoPath = $logoFile->store('partner-logos', 'public');

                PartnerLogo::create([
                    'logo_path' => $logoPath,
                    'company_name' => $validated['company_name'] ?? null,
                    'testimonial' => $validated['testimonial'] ?? null,
                    'display_order' => $maxOrder + $index + 1,
                    'is_active' => true,
                ]);

                $uploadedCount++;
            }
        }

        if ($uploadedCount === 0) {
            return back()->withErrors(['logos' => 'No valid images were uploaded.']);
        }

        $message = $uploadedCount === 1 
            ? 'Partner logo added successfully.' 
            : "{$uploadedCount} partner logos added successfully.";

        return back()->with('success', $message);
    }

    /**
     * Delete a partner logo.
     */
    public function deletePartnerLogo(string $id)
    {
        $logo = PartnerLogo::findOrFail($id);

        // Delete the file
        if (Storage::disk('public')->exists($logo->logo_path)) {
            Storage::disk('public')->delete($logo->logo_path);
        }

        $logo->delete();

        return back()->with('success', 'Partner logo deleted successfully.');
    }

    /**
     * Update partner logo order.
     */
    public function updatePartnerLogoOrder(Request $request)
    {
        $validated = $request->validate([
            'logos' => ['required', 'array'],
            'logos.*.id' => ['required', 'exists:partner_logos,id'],
            'logos.*.display_order' => ['required', 'integer'],
        ]);

        foreach ($validated['logos'] as $logoData) {
            PartnerLogo::where('id', $logoData['id'])
                ->update(['display_order' => $logoData['display_order']]);
        }

        return back()->with('success', 'Logo order updated successfully.');
    }
}
