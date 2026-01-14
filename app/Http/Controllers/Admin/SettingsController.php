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
                return [
                    'id' => $logo->id,
                    'logo_url' => Storage::disk('public')->url($logo->logo_path),
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
     * Store a new partner logo.
     */
    public function storePartnerLogo(Request $request)
    {
        $validated = $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'testimonial' => ['nullable', 'string', 'max:500'],
        ]);

        // Store the logo
        $logoPath = $request->file('logo')->store('partner-logos', 'public');

        // Get the highest display order
        $maxOrder = PartnerLogo::max('display_order') ?? 0;

        PartnerLogo::create([
            'logo_path' => $logoPath,
            'company_name' => $validated['company_name'] ?? null,
            'testimonial' => $validated['testimonial'] ?? null,
            'display_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Partner logo added successfully.');
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
