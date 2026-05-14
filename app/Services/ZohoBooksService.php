<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\ZohoSyncTimestamp;
use App\Models\ZohoInvoice;
use App\Models\ZohoBill;
use App\Models\ZohoAttachment;

/**
 * Zoho Books Service
 *
 * Handles synchronization of Invoices, Bills, and their attachments
 * from Zoho Books API to local database and storage.
 */
class ZohoBooksService
{
    private string $orgId;
    private string $dc;

    /**
     * Constructor
     *
     * Initializes organization ID and data center from config.
     */
    public function __construct()
    {
        $this->orgId = config('services.zoho.organization_id');
        $dc = config('services.zoho.data_center', '.com');
        $this->dc = str_starts_with($dc, '.') ? $dc : '.' . $dc;
    }

    /**
     * Get Zoho OAuth Access Token
     *
     * Uses refresh token to get a new access token and caches it for 55 minutes.
     *
     * @return string Access token
     * @throws \Exception If token refresh fails
     */
    private function getAccessToken(): string
    {
        return Cache::remember('zoho_access_token', 3300, function () {
            $response = Http::asForm()->post("https://accounts.zoho{$this->dc}/oauth/v2/token", [
                'refresh_token' => config('services.zoho.refresh_token'),
                'client_id' => config('services.zoho.client_id'),
                'client_secret' => config('services.zoho.client_secret'),
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful() && $response->json('access_token')) {
                return $response->json('access_token');
            }
            throw new \Exception('Zoho OAuth Refresh Failed: ' . $response->body());
        });
    }

    /**
     * Make API Request to Zoho Books
     *
     * Handles GET requests with automatic token refresh on 401 and error handling.
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param bool $isRetry Whether this is a retry after token refresh
     * @return array Parsed JSON response
     * @throws \Exception On API error or invalid response
     */
    private function makeApiRequest(string $endpoint, array $params = [], bool $isRetry = false): array
    {
        $token = $this->getAccessToken();
        $url = "https://www.zohoapis{$this->dc}/books/v3/{$endpoint}";

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
            'X-com-zoho-books-organizationid' => $this->orgId,
        ])->get($url, $params);

        if ($response->status() === 401 && !$isRetry) {
            Cache::forget('zoho_access_token');
            return $this->makeApiRequest($endpoint, $params, true);
        }

        if (!$response->successful()) {
            throw new \Exception("Zoho API Error ({$response->status()}): " . $response->body());
        }

        $data = $response->json();

        if ($data === null) {
            throw new \Exception("Zoho returned non-JSON response: " . $response->body());
        }

        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \Exception("Zoho API Error (code {$data['code']}): " . ($data['message'] ?? json_encode($data)));
        }

        return $data;
    }

    /**
     * Sync All Modules
     *
     * Runs full synchronization for invoices and bills.
     * Increases execution time limit for long-running syncs.
     */
    public function syncAll(): void
    {
        // Increase execution time for XAMPP / local environment
        ini_set('max_execution_time', '300');
        set_time_limit(300);

        $this->syncInvoices();
        $this->syncBills();

    }

    /**
     * Sync Invoices from Zoho Books
     *
     * Fetches invoices incrementally using last_modified_time,
     * saves basic data, and downloads attachments when present.
     */
    private function syncInvoices(): void
    {
        $lastSync = ZohoSyncTimestamp::where('module_name', 'invoices')->first();
        $params = ['per_page' => 100];

        if ($lastSync && $lastSync->last_modified_time) {
            $params['last_modified_time'] = \Carbon\Carbon::parse($lastSync->last_modified_time)
                ->utc()
                ->format('Y-m-d\TH:i:s+0000');
        }

        $page = 1;
        $hasMore = true;
        $processed = 0;

        while ($hasMore) {
            $params['page'] = $page;
            $data = $this->makeApiRequest('invoices', $params);


            if (!isset($data['invoices']) || !is_array($data['invoices'])) {
                logger()->error('Unexpected Zoho Invoices Response', ['data' => $data]);
                break;
            }

            foreach ($data['invoices'] as $r) {
                $processed++;

                ZohoInvoice::updateOrCreate(
                    ['invoice_id' => $r['invoice_id']],
                    [
                        'invoice_number' => $r['invoice_number'] ?? null,
                        'customer_name' => $r['customer_name'] ?? null,
                        'customer_id' => $r['customer_id'] ?? null,
                        'email' => $r['email'] ?? null,
                        'date' => $r['date'] ?? null,
                        'due_date' => $r['due_date'] ?? null,
                        'status' => $r['status'] ?? null,
                        'total' => $r['total'] ?? 0,
                        'sub_total' => $r['sub_total'] ?? 0,
                        'tax_total' => $r['tax_amount'] ?? 0,
                        'balance' => $r['balance'] ?? 0,
                        'currency_code' => $r['currency_code'] ?? 'INR',
                        'reference_number' => $r['reference_number'] ?? null,
                        'notes' => $r['notes'] ?? null,
                    ]
                );

                if (!empty($r['has_attachment'])) {

                    $fullInvoice = $this->fetchFullInvoice($r['invoice_id']);
                    $documents = $fullInvoice['documents'] ?? [];

                    foreach ($documents as $doc) {
                        $this->saveAttachment($r['invoice_id'], 'invoice', $doc);
                    }
                }
            }

            $hasMore = $data['page_context']['has_more_page'] ?? false;
            $page++;
        }

        ZohoSyncTimestamp::updateOrCreate(
            ['module_name' => 'invoices'],
            ['last_modified_time' => now()->utc()->format('Y-m-d H:i:s')]
        );
    }

    /**
     * Sync Bills from Zoho Books
     *
     * Fetches bills incrementally and handles attachments.
     */
    private function syncBills(): void
    {
        $lastSync = ZohoSyncTimestamp::where('module_name', 'bills')->first();
        $params = ['per_page' => 100];

        if ($lastSync && $lastSync->last_modified_time) {
            $params['last_modified_time'] = \Carbon\Carbon::parse($lastSync->last_modified_time)
                ->utc()
                ->format('Y-m-d\TH:i:s+0000');
        }

        $page = 1;
        $hasMore = true;
        $processed = 0;

        while ($hasMore) {
            $params['page'] = $page;
            $data = $this->makeApiRequest('bills', $params);

            if (!isset($data['bills']) || !is_array($data['bills'])) {
                logger()->error('Unexpected Zoho Bills Response', ['data' => $data]);
                break;
            }

            foreach ($data['bills'] as $r) {
                $processed++;
                ZohoBill::updateOrCreate(
                    ['bill_id' => $r['bill_id']],
                    [
                        'bill_number' => $r['bill_number'] ?? null,
                        'vendor_name' => $r['vendor_name'] ?? null,
                        'vendor_id' => $r['vendor_id'] ?? null,
                        'date' => $r['date'] ?? null,
                        'due_date' => $r['due_date'] ?? null,
                        'status' => $r['status'] ?? null,
                        'total' => $r['total'] ?? 0,
                        'sub_total' => $r['sub_total'] ?? 0,
                        'tax_total' => $r['tax_amount'] ?? 0,
                        'balance' => $r['balance'] ?? 0,
                        'currency_code' => $r['currency_code'] ?? 'INR',
                        'reference_number' => $r['reference_number'] ?? null,
                        'description' => $r['notes'] ?? null,
                    ]
                );

                if (!empty($r['has_attachment'])) {

                    $fullBill = $this->fetchFullBill($r['bill_id']);
                    $documents = $fullBill['documents'] ?? [];

                    foreach ($documents as $doc) {
                        $this->saveAttachment($r['bill_id'], 'bill', $doc);
                    }
                }
            }

            $hasMore = $data['page_context']['has_more_page'] ?? false;
            $page++;
        }

        ZohoSyncTimestamp::updateOrCreate(
            ['module_name' => 'bills'],
            ['last_modified_time' => now()->utc()->format('Y-m-d H:i:s')]
        );
    }

    /**
     * Fetch Full Invoice Details
     *
     * Required to get the complete 'documents' array (List endpoint only gives has_attachment flag).
     *
     * @param string $invoiceId Zoho Invoice ID
     * @return array Full invoice data
     */
    private function fetchFullInvoice(string $invoiceId): array
    {
        $data = $this->makeApiRequest("invoices/{$invoiceId}");
        return $data['invoice'] ?? [];
    }

    /**
     * Fetch Full Bill Details
     *
     * Required to get the complete 'documents' array.
     *
     * @param string $billId Zoho Bill ID
     * @return array Full bill data
     */
    private function fetchFullBill(string $billId): array
    {
        $data = $this->makeApiRequest("bills/{$billId}");
        return $data['bill'] ?? [];
    }

    /**
     * Download Attachment File from Zoho
     *
     * @param string $endpoint Full attachment endpoint
     * @return array ['content', 'mime_type', 'size']
     * @throws \Exception On download failure
     */
    private function downloadAttachment(string $endpoint): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
            'X-com-zoho-books-organizationid' => $this->orgId,
        ])->get("https://www.zohoapis{$this->dc}/books/v3/{$endpoint}");

        if (!$response->successful()) {
            throw new \Exception("Attachment download failed ({$response->status()}): " . $response->body());
        }

        $content = $response->body();

        return [
            'content' => $content,
            'mime_type' => $response->header('Content-Type') ?? 'application/octet-stream',
            'size' => strlen($content),
        ];
    }

    /**
     * Save Attachment to Storage and Database
     *
     * Downloads file from Zoho and stores it locally (idempotent operation).
     *
     * @param string $parentId Invoice or Bill ID
     * @param string $type 'invoice' or 'bill'
     * @param array $doc Document data from Zoho
     */
    private function saveAttachment(string $parentId, string $type, array $doc): void
    {
        $documentId = $doc['document_id'] ?? null;
        if (!$documentId) {
            return;
        }

        $fileName = $doc['file_name'] ?? 'attachment-' . $documentId;

        if (ZohoAttachment::where('document_id', $documentId)->exists()) {

            return;
        }

        $folder = $type === 'invoice' ? "invoices/{$parentId}" : "bills/{$parentId}";
        $storagePath = "{$folder}/{$fileName}";

        $endpoint = $type === 'invoice'
            ? "invoices/{$parentId}/documents/{$documentId}"
            : "bills/{$parentId}/documents/{$documentId}";



        $fileData = $this->downloadAttachment($endpoint);

        Storage::disk('public')->put($storagePath, $fileData['content']);

        ZohoAttachment::create([
            'document_id' => $documentId,
            'attachable_type' => $type === 'invoice' ? ZohoInvoice::class : ZohoBill::class,
            'attachable_id' => $parentId,
            'file_name' => $fileName,
            'file_path' => $storagePath,
            'mime_type' => $fileData['mime_type'],
            'size' => $fileData['size'],
        ]);

    }
}