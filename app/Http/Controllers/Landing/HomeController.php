<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Admin\Plan;
use App\Models\Admin\PartnerLogo;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display the landing page.
     */
    public function index(Request $request)
    {
        // Cache plans for 1 hour (3600 seconds)
        $plans = Cache::remember('landing_plans', 3600, function () {
            // Get all plans from database (excluding Enterprise if exists)
            $allPlans = Plan::where('name', '!=', 'Enterprise')
                ->orderBy('price', 'asc')
                ->get(['id', 'name', 'price', 'yearly_price', 'max_employees', 'features']);
            $totalPlans = $allPlans->count();
            
            $formattedPlans = $allPlans->map(function ($plan, $index) use ($totalPlans) {
                // Mark the middle plan as popular (if there are 3 or more plans)
                $isPopular = $totalPlans >= 3 && $index === 1;
                
                // Format features - if features is an array, use it; otherwise create default features
                $features = $plan->features ?? [];
                
                // If features is empty, create default features based on plan
                if (empty($features)) {
                    $features = [
                        "Up to {$plan->max_employees} employees",
                        'Basic employee management',
                        'Attendance tracking',
                        'Leave management',
                        'Email support',
                    ];
                }
                
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => number_format($plan->price, 0), // Remove decimals for display
                    'price_raw' => $plan->price, // Keep raw price for calculations
                    'yearly_price' => $plan->yearly_price !== null ? number_format($plan->yearly_price, 0) : null,
                    'yearly_price_raw' => $plan->yearly_price,
                    'max_employees' => $plan->max_employees,
                    'features' => $features,
                    'popular' => $isPopular,
                    'description' => $this->getPlanDescription($plan->name, $plan->max_employees),
                ];
            })
            ->values()
            ->toArray();

            // Add Enterprise plan at the end (without price)
            $enterprisePlan = [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'price' => 'Custom',
                'price_raw' => null,
                'max_employees' => null,
                'features' => [
                    'Unlimited employees',
                    'Advanced employee management',
                    'Real-time attendance tracking',
                    'Comprehensive leave management',
                    'Priority support',
                    'Custom integrations',
                    'Dedicated account manager',
                    'Advanced security & compliance',
                    'Custom reporting & analytics',
                    'API access',
                ],
                'popular' => false,
                'description' => 'Tailored solutions for large organizations',
            ];

            $formattedPlans[] = $enterprisePlan;
            return $formattedPlans;
        });

        // Cache partner logos for 1 hour
        $partnerLogos = Cache::remember('landing_partner_logos', 3600, function () {
            return PartnerLogo::where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('created_at', 'desc')
                ->get(['id', 'logo_path', 'company_name', 'testimonial'])
                ->map(function ($logo) {
                    return [
                        'id' => $logo->id,
                        'logo_url' => $logo->logo_path ? cdn_storage($logo->logo_path) : null,
                        'company_name' => $logo->company_name,
                        'testimonial' => $logo->testimonial,
                    ];
                })
                ->values()
                ->toArray();
        });

        // Cache settings texts for 1 hour
        $settingsText1 = Cache::remember('settings_text1', 3600, function () {
            return Setting::getValue('settings_text1', 'Finally, a performance management platform that works your way.');
        });
        
        $settingsText2 = Cache::remember('settings_text2', 3600, function () {
            return Setting::getValue('settings_text2', 'Bring goals, feedback, and competencies together in one place with a platform that adapts to your process — not the other way around.');
        });

        return Inertia::render('Landing/Index', [
            'plans' => $plans,
            'partnerLogos' => $partnerLogos,
            'settingsText1' => $settingsText1,
            'settingsText2' => $settingsText2,
        ]);
    }

    /**
     * Get description for plan based on name and max employees
     */
    private function getPlanDescription(string $name, int $maxEmployees): string
    {
        $nameLower = strtolower($name);
        
        if (str_contains($nameLower, 'starter') || str_contains($nameLower, 'basic')) {
            return 'Perfect for small teams getting started';
        } elseif (str_contains($nameLower, 'professional') || str_contains($nameLower, 'pro')) {
            return 'For growing businesses with advanced needs';
        } elseif (str_contains($nameLower, 'enterprise') || str_contains($nameLower, 'business')) {
            return 'Tailored solutions for large organizations';
        } elseif ($maxEmployees >= 100) {
            return 'For growing businesses with advanced needs';
        } elseif ($maxEmployees >= 50) {
            return 'Perfect for small teams getting started';
        } else {
            return 'Tailored solutions for your business';
        }
    }
}
