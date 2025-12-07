<?php

namespace App\Console\Commands;

use App\Models\Company\Employee;
use App\Models\Company\AttendanceRecord;
use App\Services\PerformanceService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CalculatePerformanceForExistingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:calculate-existing {--company-id=} {--month=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate performance scores for existing attendance data';

    protected $performanceService;

    public function __construct(PerformanceService $performanceService)
    {
        parent::__construct();
        $this->performanceService = $performanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company-id');
        $month = $this->option('month');

        if ($companyId) {
            $employees = Employee::where('company_id', $companyId)
                ->where('status', 'active')
                ->get();
        } else {
            $employees = Employee::where('status', 'active')->get();
        }

        if ($month) {
            // Calculate for specific month
            $this->info("Calculating performance for month: {$month}");
            $bar = $this->output->createProgressBar($employees->count());
            $bar->start();

            foreach ($employees as $employee) {
                $this->performanceService->calculateEmployeePerformance($employee, $month);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("✅ Performance calculated for {$month}");
        } else {
            // Calculate for all months with attendance data
            $this->info("Calculating performance for all months with attendance data...");
            
            // Get all unique months from attendance records
            $months = AttendanceRecord::selectRaw('DATE_FORMAT(datetime, "%Y-%m") as month')
                ->distinct()
                ->orderBy('month')
                ->pluck('month');

            if ($months->isEmpty()) {
                $this->warn("No attendance records found!");
                return 1;
            }

            $this->info("Found " . $months->count() . " months with attendance data");
            
            $totalBar = $this->output->createProgressBar($employees->count() * $months->count());
            $totalBar->start();

            foreach ($months as $monthKey) {
                foreach ($employees as $employee) {
                    $this->performanceService->calculateEmployeePerformance($employee, $monthKey);
                    $totalBar->advance();
                }
            }

            $totalBar->finish();
            $this->newLine();
            $this->info("✅ Performance calculated for all months");
        }

        return 0;
    }
}
