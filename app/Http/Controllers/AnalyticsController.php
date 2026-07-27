<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        // 1. Most Requested Documents (Filtered by Year/Month)
        $requestCounts = RequestDocument::select('document_type', DB::raw('count(*) as total'))
            ->when($year, function($query) use ($year) {
                return $query->whereYear('created_at', $year);
            })
            ->when($month && $month != 'all', function($query) use ($month) {
                return $query->whereMonth('created_at', $month);
            })
            ->groupBy('document_type')
            ->orderBy('total', 'desc')
            ->get();

        // 2. Request Trends (Monthly for the selected year)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthField = $isSqlite ? "CAST(strftime('%m', created_at) AS INTEGER)" : "MONTH(created_at)";
        $yearField = $isSqlite ? "CAST(strftime('%Y', created_at) AS INTEGER)" : "YEAR(created_at)";

        $trends = RequestDocument::select(
                DB::raw("$monthField as month"),
                DB::raw('count(*) as total')
            )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Fill missing months with 0
        $monthlyTrends = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyTrends[] = $trends[$i] ?? 0;
        }

        // 3. Status Distribution
        $statusCounts = RequestDocument::select('status', DB::raw('count(*) as total'))
            ->when($year, function($query) use ($year) {
                return $query->whereYear('created_at', $year);
            })
            ->groupBy('status')
            ->get();

        $years = RequestDocument::select(DB::raw("$yearField as year"))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = [date('Y')];
        }

        return view('analytics.index', compact('requestCounts', 'monthlyTrends', 'statusCounts', 'year', 'month', 'years'));
    }
}
