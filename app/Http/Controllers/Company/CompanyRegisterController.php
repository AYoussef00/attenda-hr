<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Admin\Company;
use App\Models\Admin\CompanySubscription;
use App\Models\Admin\Plan;
use App\Models\User;
use App\Models\Company\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompanyRegisterController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'company.name' => ['required', 'string', 'max:255'],
                'company.industry' => ['nullable', 'string', 'max:255'],
                'company.industry_other' => ['nullable', 'string', 'max:255', 'required_if:company.industry,Other'],
                'company.commercial_registration_no' => ['required', 'string', 'max:255', Rule::unique('companies', 'settings->commercial_registration_no')],
                'company.country' => ['nullable', 'string', 'max:255'],
                'company.city' => ['nullable', 'string', 'max:255'],
                'company.address' => ['nullable', 'string', 'max:255'],
                'company.phone' => ['nullable', 'string', 'max:255'],
                'company.email' => ['required', 'email', 'max:255', Rule::unique('companies', 'email')],
                'company.size' => ['nullable', 'string', 'max:255'],
                'admin.name' => ['required', 'string', 'max:255'],
                'admin.email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'admin.phone' => ['nullable', 'string', 'max:255'],
                'admin.password' => ['required', 'string', 'min:8', 'confirmed'],
                'plan_id' => ['required', 'integer', 'exists:plans,id'],
                'billing_period' => ['nullable', 'in:monthly,yearly'],
            ], [
                'company.name.required' => 'Company name is required.',
                'company.email.required' => 'Company email is required.',
                'company.email.unique' => 'This company email is already registered.',
                'company.commercial_registration_no.required' => 'Commercial registration number is required.',
                'company.commercial_registration_no.unique' => 'This commercial registration number is already registered.',
                'admin.name.required' => 'Admin name is required.',
                'admin.email.required' => 'Admin email is required.',
                'admin.email.unique' => 'This admin email is already registered.',
                'admin.password.required' => 'Password is required.',
                'admin.password.min' => 'Password must be at least 8 characters.',
                'admin.password.confirmed' => 'Password confirmation does not match.',
                'plan_id.required' => 'Please select a plan.',
                'plan_id.exists' => 'Selected plan is invalid.',
            ]);

            DB::beginTransaction();

            // Get plan
            $plan = Plan::findOrFail($validated['plan_id']);

            // Monthly or yearly (from landing pricing toggle)
            $billingPeriod = $validated['billing_period'] ?? 'monthly';

            // Create company with pending status (for all plans)
            $company = Company::create([
                'name' => $validated['company']['name'],
                'email' => $validated['company']['email'] ?? null,
                'phone' => $validated['company']['phone'] ?? null,
                'address' => $validated['company']['address'] ?? null,
                'status' => 'pending', // All companies start as pending
                'attendance_methods' => ['qr' => true, 'ip' => false],
                'settings' => [
                    'industry' => $validated['company']['industry'] ?? null,
                    'industry_other' => $validated['company']['industry_other'] ?? null,
                    'commercial_registration_no' => $validated['company']['commercial_registration_no'] ?? null,
                    'country' => $validated['company']['country'] ?? null,
                    'city' => $validated['company']['city'] ?? null,
                    'size' => $validated['company']['size'] ?? null,
                    // Save selected billing period for admin reference (monthly / yearly)
                    'subscription_billing_period' => $billingPeriod,
                ],
            ]);

            // Create default leave types for the new company
            $defaultLeaveTypes = [
                [
                    'name' => 'Annual Leave / Vacation',
                    'description' => 'Paid annual leave for vacation and personal time off.',
                    'yearly_balance' => 21,
                ],
                [
                    'name' => 'Sick Leave',
                    'description' => 'Leave for sickness or medical appointments.',
                    'yearly_balance' => 10,
                ],
                [
                    'name' => 'Unpaid Leave',
                    'description' => 'Unpaid leave for special cases outside the paid policy.',
                    'yearly_balance' => 0,
                ],
                [
                    'name' => 'Maternity/Paternity Leave',
                    'description' => 'Leave related to childbirth or adoption.',
                    'yearly_balance' => 90,
                ],
                [
                    'name' => 'Emergency Leave',
                    'description' => 'Short notice leave for emergencies and urgent situations.',
                    'yearly_balance' => 5,
                ],
            ];

            foreach ($defaultLeaveTypes as $type) {
                LeaveType::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $type['name'],
                    ],
                    [
                        'description' => $type['description'],
                        'yearly_balance' => $type['yearly_balance'],
                    ]
                );
            }

            // Create admin user for the company (pending status)
            $user = User::create([
                'company_id' => $company->id,
                'name' => $validated['admin']['name'],
                'email' => $validated['admin']['email'],
                'password' => Hash::make($validated['admin']['password']),
                'phone' => $validated['admin']['phone'] ?? null,
                'role' => 'company_admin',
                'status' => 'pending', // User also pending
            ]);

            // Calculate subscription dates based on billing period
            $startDate = Carbon::now();
            $endDate = $billingPeriod === 'yearly'
                ? $startDate->copy()->addYear()
                : $startDate->copy()->addMonth();

            // Create subscription with pending status
            $subscription = CompanySubscription::create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'pending', // Subscription also pending
            ]);

            DB::commit();

            // Return response
            return response()->json([
                'success' => true,
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'plan_name' => $plan->name,
                'plan_price' => $plan->price,
                'message' => 'Your request has been received. We will contact you as soon as possible.',
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log full error details
            Log::error('Company Registration Failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return user-friendly error message
            $errorMessage = 'Failed to register company. Please try again or contact support.';
            
            // In development, show more details
            if (config('app.debug')) {
                $errorMessage = 'Failed to register company: ' . $e->getMessage();
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
