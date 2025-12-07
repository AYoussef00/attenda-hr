<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Asset;
use App\Models\Company\AssetMaintenance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;
use Carbon\Carbon;

class AssetReportController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Assets by status
        $assetsByStatus = Asset::where('company_id', $company->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Assets with high maintenance cost (top 10)
        $highMaintenanceAssets = Asset::where('company_id', $company->id)
            ->withSum('maintenance', 'cost')
            ->having('maintenance_sum_cost', '>', 0)
            ->orderBy('maintenance_sum_cost', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($asset) {
                return [
                    'asset_code' => $asset->asset_code,
                    'type' => $asset->type,
                    'model' => $asset->model,
                    'total_maintenance_cost' => number_format($asset->maintenance_sum_cost ?? 0, 2),
                ];
            })
            ->toArray();

        // Assets nearing warranty expiration (within 90 days)
        $nearingWarrantyExpiration = Asset::where('company_id', $company->id)
            ->whereNotNull('warranty_end')
            ->where('warranty_end', '>=', now())
            ->where('warranty_end', '<=', now()->addDays(90))
            ->orderBy('warranty_end', 'asc')
            ->get()
            ->map(function ($asset) {
                $daysRemaining = now()->diffInDays($asset->warranty_end, false);
                return [
                    'asset_code' => $asset->asset_code,
                    'type' => $asset->type,
                    'model' => $asset->model,
                    'warranty_end' => $asset->warranty_end->format('Y-m-d'),
                    'days_remaining' => $daysRemaining,
                ];
            })
            ->toArray();

        return Inertia::render('Company/Assets/Reports/Index', [
            'assetsByStatus' => $assetsByStatus,
            'highMaintenanceAssets' => $highMaintenanceAssets,
            'nearingWarrantyExpiration' => $nearingWarrantyExpiration,
        ]);
    }

    /**
     * Export PDF report.
     */
    public function exportPdf(Request $request, string $type)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $data = [];
        $title = '';

        switch ($type) {
            case 'status':
                $title = 'Assets by Status Report';
                $assets = Asset::where('company_id', $company->id)
                    ->orderBy('status')
                    ->orderBy('asset_code')
                    ->get();
                
                $grouped = $assets->groupBy('status');
                $data = [
                    'company_name' => $company->name,
                    'report_date' => now()->format('Y-m-d H:i:s'),
                    'grouped_assets' => $grouped->map(function ($group, $status) {
                        return [
                            'status' => $status,
                            'count' => $group->count(),
                            'assets' => $group->map(function ($asset) {
                                return [
                                    'asset_code' => $asset->asset_code,
                                    'type' => $asset->type,
                                    'model' => $asset->model,
                                    'purchase_date' => $asset->purchase_date->format('Y-m-d'),
                                    'cost' => number_format($asset->cost, 2),
                                ];
                            })->toArray(),
                        ];
                    })->values()->toArray(),
                ];
                break;

            case 'maintenance':
                $title = 'High Maintenance Cost Assets Report';
                $assets = Asset::where('company_id', $company->id)
                    ->withSum('maintenance', 'cost')
                    ->having('maintenance_sum_cost', '>', 0)
                    ->orderBy('maintenance_sum_cost', 'desc')
                    ->get();
                
                $data = [
                    'company_name' => $company->name,
                    'report_date' => now()->format('Y-m-d H:i:s'),
                    'assets' => $assets->map(function ($asset) {
                        return [
                            'asset_code' => $asset->asset_code,
                            'type' => $asset->type,
                            'model' => $asset->model,
                            'purchase_date' => $asset->purchase_date->format('Y-m-d'),
                            'purchase_cost' => number_format($asset->cost, 2),
                            'total_maintenance_cost' => number_format($asset->maintenance_sum_cost ?? 0, 2),
                        ];
                    })->toArray(),
                ];
                break;

            case 'warranty':
                $title = 'Assets Nearing Warranty Expiration Report';
                $assets = Asset::where('company_id', $company->id)
                    ->whereNotNull('warranty_end')
                    ->where('warranty_end', '>=', now())
                    ->where('warranty_end', '<=', now()->addDays(90))
                    ->orderBy('warranty_end', 'asc')
                    ->get();
                
                $data = [
                    'company_name' => $company->name,
                    'report_date' => now()->format('Y-m-d H:i:s'),
                    'assets' => $assets->map(function ($asset) {
                        $daysRemaining = now()->diffInDays($asset->warranty_end, false);
                        return [
                            'asset_code' => $asset->asset_code,
                            'type' => $asset->type,
                            'model' => $asset->model,
                            'purchase_date' => $asset->purchase_date->format('Y-m-d'),
                            'warranty_end' => $asset->warranty_end->format('Y-m-d'),
                            'days_remaining' => $daysRemaining,
                        ];
                    })->toArray(),
                ];
                break;

            default:
                abort(404, 'Report type not found.');
        }

        // Generate PDF
        $html = view('pdf.asset-report', [
            'title' => $title,
            'data' => $data,
            'type' => $type,
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = strtolower(str_replace(' ', '-', $title)) . '-' . now()->format('Y-m-d') . '.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
