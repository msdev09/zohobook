<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ZohoBooksService;
use App\Models\ZohoInvoice;
use App\Models\ZohoBill;
use App\Models\MonthlyBudget;
use App\Models\ZohoAttachment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Report Controller
 *
 * Handles financial reports, budget management, transaction listing,
 * and attachment downloads from Zoho Books data.
 */
class ReportController extends Controller
{
    /**
     * Display main report dashboard
     *
     * Runs a silent delta sync, calculates current/previous month sales & COGS,
     * loads budgets, and returns the report view.
     *
     * @param ZohoBooksService $zoho Injected Zoho service
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (empty(config('services.zoho.refresh_token'))) {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            
            // Wait to ensure sessions can flash and redirect properly if hitting edge cases
            return redirect()->route('zoho.connect');
        }

        // Setup dynamic months
        $currentDate = now();
        $prevDate = now()->subMonth();

        $curKey = $currentDate->format('Y-m');
        $prevKey = $prevDate->format('Y-m');

        // 3. Fetch Sums (Exclude voids/drafts)
        $sums = [
            'curSales' => ZohoInvoice::whereMonth('date', $currentDate->month)
                ->whereYear('date', $currentDate->year)
                ->whereNotIn('status', ['void', 'draft'])
                ->sum('total'),

            'prevSales' => ZohoInvoice::whereMonth('date', $prevDate->month)
                ->whereYear('date', $prevDate->year)
                ->whereNotIn('status', ['void', 'draft'])
                ->sum('total'),

            'curCogs' => ZohoBill::whereMonth('date', $currentDate->month)
                ->whereYear('date', $currentDate->year)
                ->whereNotIn('status', ['void', 'draft'])
                ->sum('total'),

            'prevCogs' => ZohoBill::whereMonth('date', $prevDate->month)
                ->whereYear('date', $prevDate->year)
                ->whereNotIn('status', ['void', 'draft'])
                ->sum('total'),
        ];

        // 4. Budgets
        $budgets = MonthlyBudget::whereIn('month', [$curKey, $prevKey])->get();

        return view('report', [
            'curDate' => $currentDate,
            'prevDate' => $prevDate,
            'curKey' => $curKey,
            'prevKey' => $prevKey,
            'sums' => $sums,
            'budgets' => $budgets,
        ]);
    }

    /**
     * Save or update monthly budget
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveBudget(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'account' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        MonthlyBudget::updateOrCreate(
            ['month' => $validated['month'], 'account' => $validated['account']],
            ['amount' => $validated['amount']]
        );

        return response()->success([], 'Budget saved successfully');
    }

    /**
     * Get transactions for a specific month (Sales or Bills)
     *
     * Returns transactions with opening balance and all attachments.
     * Each attachment includes a direct download URL for frontend (download icon + click-to-download).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactions(Request $request)
    {
        $date = Carbon::createFromFormat('Y-m', $request->month);
        $model = $request->type === 'sales' ? ZohoInvoice::class : ZohoBill::class;
        $pk = $request->type === 'sales' ? 'invoice_id' : 'bill_id';
        $numCol = $request->type === 'sales' ? 'invoice_number' : 'bill_number';
        $nameCol = $request->type === 'sales' ? 'customer_name' : 'vendor_name';

        // Opening balance = sum of all transactions BEFORE this month
        $openingBalance = (float) $model::where('date', '<', $date->copy()->startOfMonth()->toDateString())
            ->whereNotIn('status', ['void', 'draft'])
            ->sum('total');

        // Fetch transactions with attachments
        $transactions = $model::with('attachments')
            ->whereMonth('date', $date->month)
            ->whereYear('date', $date->year)
            ->whereNotIn('status', ['void', 'draft'])
            ->orderBy('date')
            ->selectRaw("
                {$pk},
                {$numCol},
                {$nameCol},
                DATE_FORMAT(date,    '%d/%m/%Y') AS txn_date,
                DATE_FORMAT(due_date,'%d/%m/%Y') AS txn_due_date,
                status,
                total,
                sub_total,
                tax_total,
                balance,
                reference_number
            ")
            ->get();

        // Format attachments with download URL for frontend
        $transactions = $transactions->map(function ($txn) {
            $txn->attachments = $txn->attachments->map(function ($att) {
                return [
                    'document_id' => $att->document_id,
                    'file_name' => $att->file_name,
                    'mime_type' => $att->mime_type,
                    'size' => $att->size,
                    'download_url' => Storage::url($att->file_path),   // public/storage/... link
                ];
            });
            return $txn;
        });

        return response()->success([
            'transactions' => $transactions,
            'opening_balance' => $openingBalance,
        ]);
    }

    /**
     * Download a specific attachment (PDF or any file)
     *
     * Called when user clicks the download icon in the frontend.
     *
     * @param string $documentId Zoho document_id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadAttachment(string $documentId)
    {
        $attachment = ZohoAttachment::where('document_id', $documentId)->firstOrFail();

        $filePath = storage_path('app/public/' . $attachment->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $attachment->file_name);
    }

    /**
     * Trigger a manual synchronization with Zoho Books
     *
     * @param ZohoBooksService $zoho
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(ZohoBooksService $zoho)
    {
        try {
            $zoho->syncAll();
            return response()->success([], 'Zoho data synced successfully');
        } catch (\Exception $e) {
            \Log::error("Zoho Sync Error: " . $e->getMessage());
            return response()->error('Failed to sync Zoho data: ' . $e->getMessage(), 500);
        }
    }
}