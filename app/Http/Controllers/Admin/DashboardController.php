<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => Invoice::count(),
            'draft' => Invoice::where('status', 'draft')->count(),
            'sent' => Invoice::where('status', 'sent')->count(),
            'pending' => Invoice::where('status', 'pending_confirmation')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'total_revenue' => Invoice::where('status', 'paid')->sum('total_amount'),
        ];

        $recentInvoices = Invoice::with('creator')
            ->latest()
            ->take(10)
            ->get();

        $pendingProofs = Invoice::with(['latestProof'])
            ->where('status', 'pending_confirmation')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentInvoices', 'pendingProofs'));
    }
}
