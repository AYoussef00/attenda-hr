<?php

namespace App\Console\Commands;

use App\Models\Admin\Company;
use App\Services\PayrollService;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoGenerateMonthlyPayroll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:auto-generate-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate payroll for the previous month for all companies';

    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        parent::__construct();
        $this->payrollService = $payrollService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the previous month (the month that just ended)
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        
        $this->info("Auto-generating payroll for month: {$previousMonth}");

        // Get all active companies
        $companies = Company::where('status', 'active')->get();

        if ($companies->isEmpty()) {
            $this->warn("No active companies found!");
            return 0;
        }

        $this->info("Found " . $companies->count() . " active companies");

        $bar = $this->output->createProgressBar($companies->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($companies as $company) {
            try {
                // Check if payroll already exists for this month
                $existingCycle = \App\Models\Company\PayrollCycle::where('company_id', $company->id)
                    ->where('month', $previousMonth)
                    ->first();

                if ($existingCycle) {
                    $this->newLine();
                    $this->warn("Payroll cycle already exists for company {$company->name} ({$company->id}) for month {$previousMonth}");
                    $bar->advance();
                    continue;
                }

                // Check if company has any attendance records for this month
                $hasAttendance = \App\Models\Company\AttendanceRecord::where('company_id', $company->id)
                    ->whereRaw('DATE_FORMAT(datetime, "%Y-%m") = ?', [$previousMonth])
                    ->exists();

                if (!$hasAttendance) {
                    $this->newLine();
                    $this->info("No attendance records found for company {$company->name} ({$company->id}) for month {$previousMonth}");
                    $bar->advance();
                    continue;
                }

                // Generate payroll
                $result = $this->payrollService->generatePayroll($company->id, $previousMonth);
                
                $successCount++;
                Log::info("Auto-generated payroll for company {$company->id}, month {$previousMonth}", [
                    'company_id' => $company->id,
                    'month' => $previousMonth,
                    'employees_processed' => $result['summary']['processed_employees'],
                ]);

                $bar->advance();
            } catch (\Exception $e) {
                $errorCount++;
                $this->newLine();
                $this->error("Failed to generate payroll for company {$company->name} ({$company->id}): " . $e->getMessage());
                Log::error("Auto-payroll generation failed for company {$company->id}, month {$previousMonth}: " . $e->getMessage());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Auto-payroll generation completed!");
        $this->info("   - Month: {$previousMonth}");
        $this->info("   - Companies processed: {$successCount}");
        $this->info("   - Errors: {$errorCount}");

        return 0;
    }
}
