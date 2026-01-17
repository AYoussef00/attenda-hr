<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixStorageUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:fix-urls 
                            {--dry-run : Show what would be changed without making changes}
                            {--domain= : Override domain to use (defaults to APP_URL)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix hardcoded IP addresses in storage URLs stored in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $domain = $this->option('domain') ?: config('app.url');
        $baseUrl = rtrim($domain, '/');
        
        $this->info("Checking for hardcoded IPs in storage URLs...");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }
        $this->info("Using domain: {$baseUrl}");

        $totalFixed = 0;

        // Fix partner_logos table
        $totalFixed += $this->fixPartnerLogos($baseUrl, $dryRun);

        // Fix companies table logo URLs
        $totalFixed += $this->fixCompanyLogos($baseUrl, $dryRun);

        // Fix documents table file_paths if they contain full URLs
        $totalFixed += $this->fixDocuments($baseUrl, $dryRun);

        // Fix job candidates resume_paths if they contain full URLs
        $totalFixed += $this->fixJobCandidates($baseUrl, $dryRun);

        if ($dryRun) {
            $this->info("\nDRY RUN COMPLETE - {$totalFixed} records would be fixed");
            $this->info("Run without --dry-run to apply changes");
        } else {
            $this->info("\n✓ Fixed {$totalFixed} storage URLs");
        }

        return Command::SUCCESS;
    }

    /**
     * Fix partner logos URLs
     */
    private function fixPartnerLogos(string $baseUrl, bool $dryRun): int
    {
        $fixed = 0;
        $logos = DB::table('partner_logos')
            ->whereNotNull('logo_path')
            ->get();

        foreach ($logos as $logo) {
            $oldPath = $logo->logo_path;
            
            // Check if it's a full URL with IP
            if ($this->containsIpAddress($oldPath)) {
                $newPath = $this->extractRelativePath($oldPath);
                
                if ($dryRun) {
                    $this->line("Would fix partner_logos.id={$logo->id}:");
                    $this->line("  From: {$oldPath}");
                    $this->line("  To:   {$newPath}");
                } else {
                    DB::table('partner_logos')
                        ->where('id', $logo->id)
                        ->update(['logo_path' => $newPath]);
                    $this->info("Fixed partner_logos.id={$logo->id}");
                }
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Fix company logos URLs
     */
    private function fixCompanyLogos(string $baseUrl, bool $dryRun): int
    {
        $fixed = 0;
        $companies = DB::table('companies')
            ->whereNotNull('logo')
            ->get();

        foreach ($companies as $company) {
            $oldLogo = $company->logo;
            
            // Check if it's a full URL with IP
            if ($this->containsIpAddress($oldLogo)) {
                $newLogo = $this->extractRelativePath($oldLogo);
                
                if ($dryRun) {
                    $this->line("Would fix companies.id={$company->id}:");
                    $this->line("  From: {$oldLogo}");
                    $this->line("  To:   {$newLogo}");
                } else {
                    DB::table('companies')
                        ->where('id', $company->id)
                        ->update(['logo' => $newLogo]);
                    $this->info("Fixed companies.id={$company->id}");
                }
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Fix documents file_paths
     */
    private function fixDocuments(string $baseUrl, bool $dryRun): int
    {
        $fixed = 0;
        
        if (!DB::getSchemaBuilder()->hasTable('documents')) {
            return 0;
        }

        $documents = DB::table('documents')
            ->whereNotNull('file_path')
            ->get();

        foreach ($documents as $document) {
            $oldPath = $document->file_path;
            
            // Check if it's a full URL with IP
            if ($this->containsIpAddress($oldPath)) {
                $newPath = $this->extractRelativePath($oldPath);
                
                if ($dryRun) {
                    $this->line("Would fix documents.id={$document->id}:");
                    $this->line("  From: {$oldPath}");
                    $this->line("  To:   {$newPath}");
                } else {
                    DB::table('documents')
                        ->where('id', $document->id)
                        ->update(['file_path' => $newPath]);
                    $this->info("Fixed documents.id={$document->id}");
                }
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Fix job candidates resume_paths
     */
    private function fixJobCandidates(string $baseUrl, bool $dryRun): int
    {
        $fixed = 0;
        
        if (!DB::getSchemaBuilder()->hasTable('job_candidates')) {
            return 0;
        }

        $candidates = DB::table('job_candidates')
            ->whereNotNull('resume_path')
            ->get();

        foreach ($candidates as $candidate) {
            $oldPath = $candidate->resume_path;
            
            // Check if it's a full URL with IP
            if ($this->containsIpAddress($oldPath)) {
                $newPath = $this->extractRelativePath($oldPath);
                
                if ($dryRun) {
                    $this->line("Would fix job_candidates.id={$candidate->id}:");
                    $this->line("  From: {$oldPath}");
                    $this->line("  To:   {$newPath}");
                } else {
                    DB::table('job_candidates')
                        ->where('id', $candidate->id)
                        ->update(['resume_path' => $newPath]);
                    $this->info("Fixed job_candidates.id={$candidate->id}");
                }
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Check if string contains IP address
     */
    private function containsIpAddress(string $url): bool
    {
        // Check for IP pattern (e.g., 18.233.218.199)
        return (bool) preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $url);
    }

    /**
     * Extract relative path from full URL
     */
    private function extractRelativePath(string $url): string
    {
        // If it's already a relative path, return as is
        if (!preg_match('/^https?:\/\//', $url)) {
            // Remove leading slashes if present
            return ltrim($url, '/');
        }

        // Parse URL and extract path
        try {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '';
            
            // Remove /storage prefix if present (since we only store relative paths)
            $path = preg_replace('#^/storage/#', '', $path);
            $path = ltrim($path, '/');
            
            return $path;
        } catch (\Exception $e) {
            // If parsing fails, try to extract path manually
            $path = preg_replace('#^https?://[^/]+/#', '', $url);
            $path = preg_replace('#^/storage/#', '', $path);
            return ltrim($path, '/');
        }
    }
}
