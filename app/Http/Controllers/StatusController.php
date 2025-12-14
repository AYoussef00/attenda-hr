<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StatusController extends Controller
{
    /**
     * Display system status page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $appName = config('app.name');
        $environment = config('app.env');
        $appStatus = 'Running';
        
        // Safely check database connection
        $databaseStatus = 'Disconnected';
        $databaseError = null;
        
        try {
            DB::connection()->getPdo();
            $databaseStatus = 'Connected';
        } catch (\Exception $e) {
            $databaseError = $e->getMessage();
        }
        
        $serverTime = now()->format('Y-m-d H:i:s T');
        
        // Read recent logs (last 100 lines for performance)
        $logs = $this->getRecentLogs();
        
        return view('status', [
            'appName' => $appName,
            'environment' => $environment,
            'appStatus' => $appStatus,
            'databaseStatus' => $databaseStatus,
            'databaseError' => $databaseError,
            'serverTime' => $serverTime,
            'logs' => $logs,
        ]);
    }
    
    /**
     * Get recent log entries from Laravel log file
     *
     * @return array
     */
    private function getRecentLogs(): array
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            return [];
        }
        
        try {
            // Read last 200 lines for performance
            $lines = file($logPath);
            if ($lines === false) {
                return [];
            }
            
            // Get last 200 lines
            $recentLines = array_slice($lines, -200);
            
            // Parse log entries - Laravel log format: [YYYY-MM-DD HH:MM:SS] local.LEVEL: message
            $logs = [];
            $currentLog = '';
            
            foreach ($recentLines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                
                // Check if line starts a new log entry (Laravel log format)
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] local\.(\w+):/', $line)) {
                    // Save previous log if exists
                    if (!empty($currentLog)) {
                        $logs[] = trim($currentLog);
                    }
                    $currentLog = $line;
                } elseif (!empty($currentLog)) {
                    // Continue current log entry (stack traces, etc.)
                    $currentLog .= "\n" . $line;
                } else {
                    // Orphaned line, treat as separate entry
                    $logs[] = $line;
                }
            }
            
            // Add last log entry
            if (!empty($currentLog)) {
                $logs[] = trim($currentLog);
            }
            
            // Return last 50 entries, reversed (newest first)
            $result = array_reverse(array_slice($logs, -50));
            
            // If no structured logs found, return raw lines
            if (empty($result)) {
                $result = array_reverse(array_slice(array_filter($recentLines), -50));
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return ['Unable to read log file: ' . $e->getMessage()];
        }
    }
}

