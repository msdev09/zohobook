@extends('layouts.app')

@push('styles')
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background: #9ca3af;
            border-radius: 2px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        table td,
        table th {
            vertical-align: middle;
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            opacity: 1;
        }
    </style>
@endpush

@section('content')
    <div class="px-3 sm:px-5 lg:px-8 py-5">

        <!-- ============================================================
             DRILL-DOWN MODAL
        ============================================================ -->

        <div id="txn-modal" class="fixed inset-0 z-50 hidden flex items-start justify-center p-2 overflow-y-auto">

            <div class="absolute inset-0 bg-black/40 cursor-pointer" onclick="closeModal()"></div>

            <div
                class="relative bg-white border border-gray-500 w-full max-w-[98vw] min-h-[80vh] flex flex-col z-10 my-3 overflow-hidden">

                <!-- Modal Header -->
                <div class="relative border-b border-gray-400 px-5 py-4 text-center bg-[#f3f4f6]">

                    <button onclick="closeModal()"
                        class="cursor-pointer absolute right-4 top-4 w-8 h-8 rounded-sm bg-gray-200 hover:bg-red-100 text-gray-600 hover:text-red-700 transition flex items-center justify-center text-lg border border-gray-400">
                        &times;
                    </button>

                    <h5 class="text-[11px] text-gray-600 mb-1 uppercase tracking-widest font-semibold">
                        Financial Transactions
                    </h5>

                    <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">
                        Account Transactions
                    </h3>

                    <div class="flex flex-wrap items-center justify-center gap-2 text-[11px]">

                        <div class="bg-white text-gray-700 px-3 py-1 border border-gray-400 rounded-sm font-medium">
                            Basis: <strong>Accrual</strong>
                        </div>

                        <div id="modal-account-name"
                            class="bg-white text-gray-700 px-3 py-1 border border-gray-400 rounded-sm font-medium">
                        </div>

                        <div id="modal-date-range"
                            class="bg-white text-gray-700 px-3 py-1 border border-gray-400 rounded-sm font-medium">
                        </div>

                    </div>
                </div>

                <!-- Summary -->
                <div id="modal-summary"
                    class="flex gap-6 px-5 py-3 bg-gray-100 border-b border-gray-400 text-[11px] font-medium text-gray-700">
                </div>

                <!-- Table -->
                <div class="overflow-auto flex-1">

                    <table class="w-full min-w-[1350px] text-[12px] border-collapse table-fixed">

                        <thead id="modal-thead"
                            class="sticky top-0 bg-[#e5e7eb] text-gray-900 uppercase text-[11px] tracking-wide">
                        </thead>

                        <tbody id="modal-tbody" class="divide-y divide-gray-200">
                        </tbody>

                    </table>

                    <p id="modal-empty" class="hidden text-center text-gray-500 py-10 text-sm">
                        No transactions found for this period.
                    </p>

                </div>
            </div>
        </div>

        <!-- ============================================================
             MAIN REPORT
        ============================================================ -->

        <div class="max-w-[1600px] mx-auto bg-white border border-gray-400 overflow-hidden">

            <!-- Header -->
            <div class="bg-blue-700 px-5 py-3 border-b border-gray-500 flex justify-between items-center">

                <h1
                    class="text-lg lg:text-xl font-semibold text-white tracking-wide uppercase bg-blue-700 px-6 py-3 rounded-lg inline-block">
                    Zoho Books – Variance Report
                </h1>

                <button onclick="syncZohoData()" id="sync-btn"
                    class="cursor-pointer bg-white text-blue-700 font-bold px-4 py-2 text-xs uppercase tracking-wider rounded shadow hover:bg-gray-100 flex items-center gap-2 transition disabled:opacity-75 disabled:cursor-wait">
                    <i class="fa fa-sync-alt" id="sync-icon"></i>
                    <span>Sync Data</span>
                </button>

            </div>

            <!-- Table -->
            <div class="overflow-x-auto">

                <table class="min-w-[1200px] w-full text-[12px] border-collapse table-fixed">

                    <thead class="bg-[#e5e7eb] text-gray-900 uppercase text-[11px] tracking-wide">

                        <tr>

                            <th class="border border-gray-400 px-3 py-2 font-semibold text-right">
                                {{ $curDate->format('F Y') }}
                            </th>

                            <th class="border border-gray-400 px-3 py-2 font-semibold text-right">
                                Budget (Put Manually)
                            </th>

                            <th class="border border-gray-400 px-3 py-2 font-semibold text-right">
                                Variance
                            </th>

                            <th class="border border-gray-400 px-3 py-2 font-semibold text-left bg-[#d1d5db]">
                                Profit & Loss
                            </th>

                            <th class="border border-gray-400 px-3 py-2 font-semibold text-right">
                                {{ $prevDate->format('F Y') }}
                            </th>

                            <th class="border border-gray-400 px-3 py-2 font-semibold text-right">
                                Budget
                            </th>

                            <th class="border border-gray-400 px-3 py-2 font-semibold text-right">
                                Variance
                            </th>

                        </tr>

                        <tr class="bg-[#f3f4f6] text-gray-700">

                            <th class="border border-gray-400 px-3 py-2 text-right">
                                A
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-right">
                                B
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-right">
                                C=A-B
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-left">
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-right">
                                D
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-right">
                                E
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-right">
                                F=D-E
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-200">

                        <!-- Operating Income Header -->
                        <tr class="bg-[#d1d5db] font-semibold uppercase text-[11px] tracking-wide">

                            <td class="border border-gray-400 px-3 py-2"></td>
                            <td class="border border-gray-400 px-3 py-2"></td>
                            <td class="border border-gray-400 px-3 py-2"></td>

                            <td class="border border-gray-400 px-3 py-2 font-bold text-left">
                                Operating Income
                            </td>

                            <td class="border border-gray-400 px-3 py-2"></td>
                            <td class="border border-gray-400 px-3 py-2"></td>
                            <td class="border border-gray-400 px-3 py-2"></td>

                        </tr>

                        <!-- Sales Row -->
                        <tr class="bg-white">

                            <td class="border border-gray-400 px-3 py-2 text-right font-medium tabular-nums">

                                <a href="javascript:void(0)"
                                    onclick="showTransactions('sales','{{ $curKey }}','Sales','{{ $curDate->startOfMonth()->format('d/m/Y') }}','{{ $curDate->endOfMonth()->format('d/m/Y') }}')"
                                    class="text-blue-700 font-semibold hover:underline">

                                    ₹ {{ number_format($sums['curSales'], 2) }}

                                </a>

                            </td>

                            <td class="border border-gray-400 px-3 py-2 text-right">

                                ₹

                                <input id="b_curSales" type="number" step="0.01" tabindex="1"
                                    value="{{ $budgets->where('month', $curKey)->where('account', 'Sales')->first()->amount ?? 0 }}"
                                    class="w-28 text-right border border-gray-400 rounded-none px-2 py-1 bg-white text-[12px] font-medium tabular-nums focus:outline-none focus:ring-0"
                                    onchange="saveBudget('{{ $curKey }}','Sales',this.value); recalculate();">

                            </td>

                            <td id="c_curSales"
                                class="border border-gray-400 px-3 py-2 text-right font-medium tabular-nums"></td>

                            <td class="border border-gray-400 px-3 py-2 text-left font-medium pl-6">
                                Sales
                            </td>

                            <td class="border border-gray-400 px-3 py-2 text-right font-medium tabular-nums">

                                <a href="javascript:void(0)"
                                    onclick="showTransactions('sales','{{ $prevKey }}','Sales','{{ $prevDate->startOfMonth()->format('d/m/Y') }}','{{ $prevDate->endOfMonth()->format('d/m/Y') }}')"
                                    class="text-blue-700 font-semibold hover:underline">

                                    ₹ {{ number_format($sums['prevSales'], 2) }}

                                </a>

                            </td>

                            <td class="border border-gray-400 px-3 py-2 text-right">

                                ₹

                                <input id="b_prevSales" type="number" tabindex="2" step="0.01"
                                    value="{{ $budgets->where('month', $prevKey)->where('account', 'Sales')->first()->amount ?? 0 }}"
                                    class="w-28 text-right border border-gray-400 rounded-none px-2 py-1 bg-white text-[12px] font-medium tabular-nums focus:outline-none focus:ring-0"
                                    onchange="saveBudget('{{ $prevKey }}','Sales',this.value); recalculate();">

                            </td>

                            <td id="f_prevSales"
                                class="border border-gray-400 px-3 py-2 text-right font-medium tabular-nums"></td>

                        </tr>

                        <!-- Total Sales -->
                        <tr class="bg-[#f3f4f6] font-bold border-t-2 border-gray-600">

                            <td id="tot_curSales_a" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td id="tot_curSales_b" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td id="tot_curSales_c" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td class="border border-gray-400 px-3 py-2 text-left">
                                Total for Operating Income
                            </td>

                            <td id="tot_prevSales_a" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td id="tot_prevSales_b" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td id="tot_prevSales_c" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                        </tr>

                        <!-- Spacer -->
                        <tr>
                            <td colspan="7" class="p-1 border border-gray-400"></td>
                        </tr>

                        <!-- COGS Header -->
                        <tr class="bg-[#d1d5db] font-semibold uppercase text-[11px] tracking-wide">

                            <td class="border border-gray-400 px-3 py-2"></td>
                            <td class="border border-gray-400 px-3 py-2"></td>
                            <td class="border border-gray-400 px-3 py-2"></td>

                            <td class="border border-gray-400 px-3 py-2 font-bold text-left">
                                Cost of Goods Sold
                            </td>

                            <td class="border border-gray-400 px-3 py-2"></td>
                            <td class="border border-gray-400 px-3 py-2"></td>
                            <td class="border border-gray-400 px-3 py-2"></td>

                        </tr>

                        <!-- COGS Row -->
                        <tr class="bg-white">

                            <td class="border border-gray-400 px-3 py-2 text-right font-medium tabular-nums">

                                <a href="javascript:void(0)"
                                    onclick="showTransactions('cogs','{{ $curKey }}','Cost of Goods Sold','{{ $curDate->startOfMonth()->format('d/m/Y') }}','{{ $curDate->endOfMonth()->format('d/m/Y') }}')"
                                    class="text-blue-700 font-semibold hover:underline">

                                    ₹ {{ number_format($sums['curCogs'], 2) }}

                                </a>

                            </td>

                            <td class="border border-gray-400 px-3 py-2 text-right">

                                ₹

                                <input id="b_curCogs" type="number" tabindex="3" step="0.01"
                                    value="{{ $budgets->where('month', $curKey)->where('account', 'Cost of Goods Sold')->first()->amount ?? 0 }}"
                                    class="w-28 text-right border border-gray-400 rounded-none px-2 py-1 bg-white text-[12px] font-medium tabular-nums focus:outline-none focus:ring-0"
                                    onchange="saveBudget('{{ $curKey }}','Cost of Goods Sold',this.value); recalculate();">

                            </td>

                            <td id="c_curCogs"
                                class="border border-gray-400 px-3 py-2 text-right font-medium tabular-nums"></td>

                            <td class="border border-gray-400 px-3 py-2 text-left font-medium pl-6">
                                Cost of Goods Sold
                            </td>

                            <td class="border border-gray-400 px-3 py-2 text-right font-medium tabular-nums">

                                <a href="javascript:void(0)"
                                    onclick="showTransactions('cogs','{{ $prevKey }}','Cost of Goods Sold','{{ $prevDate->startOfMonth()->format('d/m/Y') }}','{{ $prevDate->endOfMonth()->format('d/m/Y') }}')"
                                    class="text-blue-700 font-semibold hover:underline">

                                    ₹ {{ number_format($sums['prevCogs'], 2) }}

                                </a>

                            </td>

                            <td class="border border-gray-400 px-3 py-2 text-right">

                                ₹

                                <input id="b_prevCogs" type="number" tabindex="4" step="0.01"
                                    value="{{ $budgets->where('month', $prevKey)->where('account', 'Cost of Goods Sold')->first()->amount ?? 0 }}"
                                    class="w-28 text-right border border-gray-400 rounded-none px-2 py-1 bg-white text-[12px] font-medium tabular-nums focus:outline-none focus:ring-0"
                                    onchange="saveBudget('{{ $prevKey }}','Cost of Goods Sold',this.value); recalculate();">

                            </td>

                            <td id="f_prevCogs"
                                class="border border-gray-400 px-3 py-2 text-right font-medium tabular-nums"></td>

                        </tr>

                        <!-- Total COGS -->
                        <tr class="bg-[#f3f4f6] font-bold border-t-2 border-gray-600">

                            <td id="tot_curCogs_a" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td id="tot_curCogs_b" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td id="tot_curCogs_c" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td class="border border-gray-400 px-3 py-2 text-left">
                                Total for Cost of Goods Sold
                            </td>

                            <td id="tot_prevCogs_a" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td id="tot_prevCogs_b" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                            <td id="tot_prevCogs_c" class="border border-gray-400 px-3 py-2 text-right tabular-nums">
                            </td>

                        </tr>

                        <!-- Spacer -->
                        <tr>
                            <td colspan="7" class="p-1 border border-gray-400"></td>
                        </tr>

                        <!-- Net Profit -->
                        <tr class="bg-[#dbeafe] font-bold text-[13px] border-t-2 border-b-2 border-gray-700">

                            <td id="net_cur_a" class="border border-gray-400 px-3 py-3 text-right tabular-nums"></td>

                            <td id="net_cur_b" class="border border-gray-400 px-3 py-3 text-right tabular-nums"></td>

                            <td id="net_cur_c" class="border border-gray-400 px-3 py-3 text-right tabular-nums"></td>

                            <td class="border border-gray-400 px-3 py-3 text-left">
                                Net Profit/Loss
                            </td>

                            <td id="net_prev_a" class="border border-gray-400 px-3 py-3 text-right tabular-nums"></td>

                            <td id="net_prev_b" class="border border-gray-400 px-3 py-3 text-right tabular-nums"></td>

                            <td id="net_prev_c" class="border border-gray-400 px-3 py-3 text-right tabular-nums"></td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>

        let currentTransactions = [];

        const ACTUALS = {
            curSales: {{ $sums['curSales'] }},
            prevSales: {{ $sums['prevSales'] }},
            curCogs: {{ $sums['curCogs'] }},
            prevCogs: {{ $sums['prevCogs'] }},
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function syncZohoData() {
            const btn = document.getElementById('sync-btn');
            const icon = document.getElementById('sync-icon');
            const text = btn.querySelector('span');

            btn.disabled = true;
            icon.classList.add('fa-spin');
            text.textContent = 'Syncing...';

            fetch('/api/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Data synced successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast('Sync failed: ' + (data.message || 'Unknown error'), 'error');
                        resetSyncBtn();
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Sync failed due to network error.', 'error');
                    resetSyncBtn();
                });
        }

        function resetSyncBtn() {
            const btn = document.getElementById('sync-btn');
            const icon = document.getElementById('sync-icon');
            const text = btn.querySelector('span');
            btn.disabled = false;
            icon.classList.remove('fa-spin');
            text.textContent = 'Sync Data';
        }

        function inr(n) {
            const num = parseFloat(n) || 0;
            const abs = Math.abs(num).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return (num < 0 ? '-' : '') + '₹ ' + abs;
        }

        function fmt(n) {
            const num = parseFloat(n) || 0;
            const abs = Math.abs(num).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return (num < 0 ? '-' : '') + '₹ ' + abs;
        }

        function set(id, value, isNegBad = true) {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = inr(value);
            el.classList.remove('text-red-700', 'text-blue-700');
            if (isNegBad) {
                el.classList.add(value >= 0 ? 'text-blue-700' : 'text-red-700');
            } else {
                el.classList.add(value <= 0 ? 'text-blue-700' : 'text-red-700');
            }
        }

        function recalculate() {
            const bCS = parseFloat(document.getElementById('b_curSales').value) || 0;
            const bPS = parseFloat(document.getElementById('b_prevSales').value) || 0;
            const bCC = parseFloat(document.getElementById('b_curCogs').value) || 0;
            const bPC = parseFloat(document.getElementById('b_prevCogs').value) || 0;

            const A = ACTUALS.curSales, B = bCS;
            const D = ACTUALS.prevSales, E = bPS;
            const Ac = ACTUALS.curCogs, Bc = bCC;
            const Dc = ACTUALS.prevCogs, Ec = bPC;

            set('c_curSales', A - B, true);
            set('f_prevSales', D - E, true);

            document.getElementById('tot_curSales_a').textContent = inr(A);
            document.getElementById('tot_curSales_b').textContent = inr(B);
            set('tot_curSales_c', A - B, true);

            document.getElementById('tot_prevSales_a').textContent = inr(D);
            document.getElementById('tot_prevSales_b').textContent = inr(E);
            set('tot_prevSales_c', D - E, true);

            set('c_curCogs', Ac - Bc, false);
            set('f_prevCogs', Dc - Ec, false);

            document.getElementById('tot_curCogs_a').textContent = inr(Ac);
            document.getElementById('tot_curCogs_b').textContent = inr(Bc);
            set('tot_curCogs_c', Ac - Bc, false);

            document.getElementById('tot_prevCogs_a').textContent = inr(Dc);
            document.getElementById('tot_prevCogs_b').textContent = inr(Ec);
            set('tot_prevCogs_c', Dc - Ec, false);

            const netCurA = A - Ac, netCurB = B - Bc;
            const netPrevA = D - Dc, netPrevB = E - Ec;

            document.getElementById('net_cur_a').textContent = inr(netCurA);
            document.getElementById('net_cur_b').textContent = inr(netCurB);
            set('net_cur_c', netCurA - netCurB, true);

            document.getElementById('net_prev_a').textContent = inr(netPrevA);
            document.getElementById('net_prev_b').textContent = inr(netPrevB);
            set('net_prev_c', netPrevA - netPrevB, true);
        }

        recalculate();

        function saveBudget(month, account, amount) {
            fetch('/api/budgets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    month,
                    account,
                    amount: parseFloat(amount) || 0
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) showToast('Budget saved successfully!', 'success');
                    else showToast('Failed to save budget: ' + (data.message || 'Unknown error'), 'error');
                })
                .catch(() => showToast('Network error while saving budget.', 'error'));
        }

        /**
         * Download using secure controller route
         */
        function downloadAttachments(index) {
            const txn = currentTransactions[index];
            if (!txn || !txn.attachments || txn.attachments.length === 0) return;

            txn.attachments.forEach(att => {
                const url = `/attachments/${att.document_id}/download`;
                const link = document.createElement('a');
                link.href = url;
                link.download = att.file_name || 'attachment.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }

        function showTransactions(type, month, accountLabel, fromDate, toDate) {

            document.getElementById('txn-modal').classList.remove('hidden');

            document.getElementById('modal-account-name').textContent = accountLabel;
            document.getElementById('modal-date-range').textContent = `From ${fromDate} to ${toDate}`;

            document.getElementById('modal-summary').innerHTML = '';
            document.getElementById('modal-thead').innerHTML = '';
            document.getElementById('modal-tbody').innerHTML = '<tr><td colspan="10" class="text-center py-8 text-gray-400">Loading…</td></tr>';
            document.getElementById('modal-empty').classList.add('hidden');

            fetch(`/api/transactions?type=${type}&month=${month}`)
                .then(r => r.json())
                .then(apiResponse => {

                    const data = apiResponse.data || apiResponse;
                    currentTransactions = data.transactions || [];
                    const openingBalance = data.opening_balance || 0;
                    const isInvoice = (type === 'sales');

                    const accountName = isInvoice ? 'Sales' : 'Cost of Goods Sold';

                    if (currentTransactions.length === 0 && openingBalance === 0) {
                        document.getElementById('modal-tbody').innerHTML = '';
                        document.getElementById('modal-empty').classList.remove('hidden');
                        return;
                    }

                    document.getElementById('modal-thead').innerHTML = `
                        <tr class="bg-[#e5e7eb] text-gray-900 text-[11px] font-bold border-b-2 border-gray-400 uppercase">
                            <th class="text-left px-3 py-2 border-r border-gray-400">Date</th>
                            <th class="text-left px-3 py-2 border-r border-gray-400">Account</th>
                            <th class="text-left px-3 py-2 border-r border-gray-400">Transaction Details</th>
                            <th class="text-left px-3 py-2 border-r border-gray-400">Transaction Type</th>
                            <th class="text-left px-3 py-2 border-r border-gray-400">Transaction#</th>
                            <th class="text-left px-3 py-2 border-r border-gray-400">Reference#</th>
                            <th class="text-center px-3 py-2 border-r border-gray-400">Attachments</th>
                            <th class="text-right px-3 py-2 border-r border-gray-400">Debit</th>
                            <th class="text-right px-3 py-2 border-r border-gray-400">Credit</th>
                            <th class="text-right px-3 py-2">Amount</th>
                        </tr>`;

                    let totalDebit = 0;
                    let totalCredit = 0;
                    let html = '';

                    html += `
                        <tr class="bg-gray-50 font-semibold text-sm border-b border-gray-300">
                            <td class="px-3 py-2 border-r border-gray-300">As On ${fromDate}</td>
                            <td class="px-3 py-2 border-r border-gray-300">Opening Balance</td>
                            <td class="px-3 py-2 border-r border-gray-300"></td>
                            <td class="px-3 py-2 border-r border-gray-300"></td>
                            <td class="px-3 py-2 border-r border-gray-300"></td>
                            <td class="px-3 py-2 border-r border-gray-300"></td>
                            <td class="px-3 py-2 border-r border-gray-300 text-center">—</td>
                            <td class="px-3 py-2 text-right border-r border-gray-300">${openingBalance != 0 && !isInvoice ? fmt(openingBalance) : '—'}</td>
                            <td class="px-3 py-2 text-right border-r border-gray-300">${openingBalance != 0 && isInvoice ? fmt(openingBalance) : '—'}</td>
                            <td class="px-3 py-2 text-right"></td>
                        </tr>`;

                    currentTransactions.forEach((t, index) => {
                        const name = isInvoice ? (t.customer_name || '—') : (t.vendor_name || '—');
                        const txnNum = isInvoice ? (t.invoice_number || t.invoice_id) : (t.bill_number || t.bill_id);
                        const txnType = isInvoice ? 'Invoice' : 'Bill';
                        const amount = parseFloat(t.total || 0);
                        const debit = !isInvoice ? amount : 0;
                        const credit = isInvoice ? amount : 0;

                        totalDebit += debit;
                        totalCredit += credit;

                        let attachmentsHtml = `<span class="text-gray-300">—</span>`;
                        if (t.attachments && t.attachments.length > 0) {
                            const countBadge = t.attachments.length > 1
                                ? `<span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 rounded-full ml-1">${t.attachments.length}</span>`
                                : '';
                            attachmentsHtml = `
                                <div onclick="event.stopImmediatePropagation(); downloadAttachments(${index})"
                                     class="inline-flex items-center gap-1 cursor-pointer hover:text-blue-600 text-blue-700">
                                    <i class="fa fa-paperclip"></i>
                                    ${countBadge}
                                </div>`;
                        }

                        html += `
                            <tr class="bg-white border-b border-gray-200">
                                <td class="px-3 py-2 border-r border-gray-300">${t.txn_date || '—'}</td>
                                <td class="px-3 py-2 border-r border-gray-300">${accountName}</td>
                                <td class="px-3 py-2 border-r border-gray-300 font-medium">${name}</td>
                                <td class="px-3 py-2 border-r border-gray-300">${txnType}</td>
                                <td class="px-3 py-2 border-r border-gray-300 text-blue-700 font-medium">${txnNum}</td>
                                <td class="px-3 py-2 border-r border-gray-300 text-gray-500">${t.reference_number || '—'}</td>
                                <td class="px-3 py-2 border-r border-gray-300 text-center">${attachmentsHtml}</td>
                                <td class="px-3 py-2 text-right border-r border-gray-300 tabular-nums">${debit > 0 ? fmt(debit) : ''}</td>
                                <td class="px-3 py-2 text-right border-r border-gray-300 tabular-nums">${credit > 0 ? fmt(credit) : ''}</td>
                                <td class="px-3 py-2 text-right font-semibold tabular-nums">${fmt(amount)} ${!isInvoice ? 'Dr' : 'Cr'}</td>
                            </tr>`;
                    });

                    html += `
                        <tr class="bg-[#f3f4f6] text-sm font-bold border-t-2 border-gray-500">
                            <td class="px-3 py-2 border-r border-gray-300" colspan="7">
                                Total Debits and Credits (${fromDate} – ${toDate})
                            </td>
                            <td class="px-3 py-2 text-right border-r border-gray-300 tabular-nums">${fmt(totalDebit)}</td>
                            <td class="px-3 py-2 text-right border-r border-gray-300 tabular-nums">${fmt(totalCredit)}</td>
                            <td class="px-3 py-2"></td>
                        </tr>`;

                    const closing = isInvoice ? (openingBalance + totalCredit) : (openingBalance + totalDebit);

                    html += `
                        <tr class="bg-gray-50 text-sm font-semibold border-t border-gray-300">
                            <td class="px-3 py-2 border-r border-gray-300">As On ${toDate}</td>
                            <td class="px-3 py-2 border-r border-gray-300">Closing Balance</td>
                            <td colspan="5" class="px-3 py-2 border-r border-gray-300"></td>
                            <td class="px-3 py-2 text-right border-r border-gray-300 tabular-nums">${fmt(closing)}</td>
                            <td class="px-3 py-2 border-r border-gray-300"></td>
                            <td class="px-3 py-2"></td>
                        </tr>`;

                    document.getElementById('modal-tbody').innerHTML = html;

                    const paid = currentTransactions.filter(t => t.status === 'paid').length;
                    document.getElementById('modal-summary').innerHTML = `
                        <span><i class="fa fa-list mr-1"></i> ${currentTransactions.length} transactions</span>
                        <span class="text-blue-700 ml-4"><i class="fa fa-check-circle mr-1"></i> ${paid} paid</span>
                        <span class="text-red-700 ml-4"><i class="fa fa-clock mr-1"></i> ${currentTransactions.length - paid} pending</span>`;
                });
        }

        function closeModal() {
            document.getElementById('txn-modal').classList.add('hidden');
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal();
        });
    </script>
@endpush