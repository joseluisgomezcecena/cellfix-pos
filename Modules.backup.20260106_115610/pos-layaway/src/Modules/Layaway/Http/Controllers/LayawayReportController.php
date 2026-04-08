<?php

namespace Modules\Layaway\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Layaway\Entities\Layaway;
use Modules\Layaway\Entities\LayawayPayment;
use App\BusinessLocation;
use App\Contact;
use DB;
use Carbon\Carbon;

class LayawayReportController extends Controller
{
    /**
     * Display reports dashboard
     * @return Renderable
     */
    public function index()
    {
        if (!auth()->user()->can('layaway.reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $stats = $this->getOverallStats($business_id);
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('layaway::reports.index', compact('stats', 'business_locations'));
    }

    /**
     * Get layaway summary report
     * @param Request $request
     * @return Renderable
     */
    public function summary(Request $request)
    {
        if (!auth()->user()->can('layaway.reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $query = Layaway::where('business_id', $business_id)
            ->with(['contact', 'location']);

        if ($request->has('location_id')) {
            $query->where('business_location_id', $request->location_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        $layaways = $query->get();

        $summary = [
            'total_layaways' => $layaways->count(),
            'total_value' => $layaways->sum('total_amount'),
            'total_collected' => 0,
            'total_pending' => $layaways->sum('balance_due'),
            'by_status' => $layaways->groupBy('status')->map(function ($items, $key) {
                return [
                    'count' => $items->count(),
                    'value' => $items->sum('total_amount'),
                    'balance' => $items->sum('balance_due')
                ];
            }),
            'overdue_count' => $layaways->filter(function ($layaway) {
                return $layaway->isOverdue();
            })->count(),
            'overdue_amount' => $layaways->filter(function ($layaway) {
                return $layaway->isOverdue();
            })->sum('balance_due')
        ];

        foreach ($layaways as $layaway) {
            $summary['total_collected'] += $layaway->payments()->sum('amount');
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('layaway::reports.summary', compact('summary', 'business_locations', 'request'));
    }

    /**
     * Get collection forecast report
     * @param Request $request
     * @return Renderable
     */
    public function collectionForecast(Request $request)
    {
        if (!auth()->user()->can('layaway.reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $forecast = Layaway::where('business_id', $business_id)
            ->where('status', 'active')
            ->where('balance_due', '>', 0)
            ->with(['contact', 'location'])
            ->orderBy('payment_deadline')
            ->get()
            ->groupBy(function ($layaway) {
                return Carbon::parse($layaway->payment_deadline)->format('Y-m');
            })
            ->map(function ($layaways, $month) {
                return [
                    'month' => $month,
                    'count' => $layaways->count(),
                    'expected_amount' => $layaways->sum('balance_due'),
                    'layaways' => $layaways
                ];
            });

        return view('layaway::reports.collection_forecast', compact('forecast'));
    }

    /**
     * Get customer layaway history
     * @param Request $request
     * @return Renderable
     */
    public function customerHistory(Request $request)
    {
        if (!auth()->user()->can('layaway.reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $customers = Contact::customersDropdown($business_id, false);

        $history = [];
        if ($request->has('contact_id')) {
            $contact_id = $request->contact_id;

            $layaways = Layaway::where('business_id', $business_id)
                ->where('contact_id', $contact_id)
                ->with(['payments', 'items.product'])
                ->orderBy('created_at', 'desc')
                ->get();

            $history = [
                'customer' => Contact::find($contact_id),
                'layaways' => $layaways,
                'total_layaways' => $layaways->count(),
                'total_value' => $layaways->sum('total_amount'),
                'total_paid' => 0,
                'total_pending' => $layaways->sum('balance_due'),
                'completed' => $layaways->where('status', 'completed')->count(),
                'active' => $layaways->where('status', 'active')->count(),
                'cancelled' => $layaways->where('status', 'cancelled')->count()
            ];

            foreach ($layaways as $layaway) {
                $history['total_paid'] += $layaway->payments()->sum('amount');
            }
        }

        return view('layaway::reports.customer_history', compact('customers', 'history', 'request'));
    }

    /**
     * Get overdue layaways report
     * @param Request $request
     * @return Renderable
     */
    public function overdue(Request $request)
    {
        if (!auth()->user()->can('layaway.reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $query = Layaway::where('business_id', $business_id)
            ->overdue()
            ->with(['contact', 'location', 'payments']);

        if ($request->has('location_id')) {
            $query->where('business_location_id', $request->location_id);
        }

        $overdue_layaways = $query->get()->map(function ($layaway) {
            $days_overdue = Carbon::now()->diffInDays(Carbon::parse($layaway->payment_deadline));
            $layaway->days_overdue = $days_overdue;
            $layaway->total_paid = $layaway->payments()->sum('amount');
            return $layaway;
        });

        $business_locations = BusinessLocation::forDropdown($business_id);

        $stats = [
            'total_overdue' => $overdue_layaways->count(),
            'total_amount_due' => $overdue_layaways->sum('balance_due'),
            'average_days_overdue' => $overdue_layaways->avg('days_overdue'),
            'oldest_overdue' => $overdue_layaways->max('days_overdue')
        ];

        return view('layaway::reports.overdue', compact('overdue_layaways', 'stats', 'business_locations', 'request'));
    }

    /**
     * Get payment methods report
     * @param Request $request
     * @return Renderable
     */
    public function paymentMethods(Request $request)
    {
        if (!auth()->user()->can('layaway.reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $query = LayawayPayment::whereHas('layaway', function ($q) use ($business_id) {
            $q->where('business_id', $business_id);
        });

        if ($request->has('start_date') && $request->has('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('payment_date', [$start, $end]);
        }

        $payments_by_method = $query->get()
            ->groupBy('payment_method')
            ->map(function ($payments, $method) {
                return [
                    'method' => $method,
                    'count' => $payments->count(),
                    'total' => $payments->sum('amount')
                ];
            });

        return view('layaway::reports.payment_methods', compact('payments_by_method', 'request'));
    }

    /**
     * Export report to CSV/Excel
     * @param Request $request
     * @param string $type
     * @return Response
     */
    public function export(Request $request, $type)
    {
        if (!auth()->user()->can('layaway.reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        switch ($type) {
            case 'summary':
                return $this->exportSummary($request, $business_id);
            case 'overdue':
                return $this->exportOverdue($request, $business_id);
            case 'customer':
                return $this->exportCustomerHistory($request, $business_id);
            default:
                abort(404, 'Export type not found');
        }
    }

    /**
     * Get overall statistics
     * @param int $business_id
     * @return array
     */
    private function getOverallStats($business_id)
    {
        $stats = [
            'total_active' => Layaway::where('business_id', $business_id)
                ->where('status', 'active')
                ->count(),
            'total_pending' => Layaway::where('business_id', $business_id)
                ->where('status', 'pending')
                ->count(),
            'total_completed_this_month' => Layaway::where('business_id', $business_id)
                ->where('status', 'completed')
                ->whereMonth('updated_at', Carbon::now()->month)
                ->count(),
            'total_overdue' => Layaway::where('business_id', $business_id)
                ->overdue()
                ->count(),
            'pending_collection' => Layaway::where('business_id', $business_id)
                ->whereIn('status', ['pending', 'active'])
                ->sum('balance_due'),
            'collected_today' => LayawayPayment::whereHas('layaway', function ($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })->whereDate('payment_date', today())
                ->sum('amount'),
            'collected_this_week' => LayawayPayment::whereHas('layaway', function ($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })->whereBetween('payment_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->sum('amount'),
            'collected_this_month' => LayawayPayment::whereHas('layaway', function ($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })->whereMonth('payment_date', Carbon::now()->month)
                ->sum('amount')
        ];

        return $stats;
    }

    /**
     * Export summary report
     */
    private function exportSummary($request, $business_id)
    {
        $query = Layaway::where('business_id', $business_id);

        if ($request->has('location_id')) {
            $query->where('business_location_id', $request->location_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        $layaways = $query->with(['contact', 'location', 'payments'])->get();

        $csv_data = [];
        $csv_data[] = ['Layaway Number', 'Customer', 'Location', 'Status', 'Total Amount', 'Paid Amount', 'Balance Due', 'Payment Deadline', 'Created Date'];

        foreach ($layaways as $layaway) {
            $csv_data[] = [
                $layaway->layaway_number,
                $layaway->contact->name ?? '',
                $layaway->location->name ?? '',
                $layaway->status,
                $layaway->total_amount,
                $layaway->payments()->sum('amount'),
                $layaway->balance_due,
                $layaway->payment_deadline,
                $layaway->created_at
            ];
        }

        $filename = 'layaway_summary_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($csv_data) {
            $file = fopen('php://output', 'w');
            foreach ($csv_data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export overdue report
     */
    private function exportOverdue($request, $business_id)
    {
        $query = Layaway::where('business_id', $business_id)->overdue();

        if ($request->has('location_id')) {
            $query->where('business_location_id', $request->location_id);
        }

        $layaways = $query->with(['contact', 'location', 'payments'])->get();

        $csv_data = [];
        $csv_data[] = ['Layaway Number', 'Customer', 'Phone', 'Email', 'Balance Due', 'Days Overdue', 'Payment Deadline', 'Last Payment Date'];

        foreach ($layaways as $layaway) {
            $days_overdue = Carbon::now()->diffInDays(Carbon::parse($layaway->payment_deadline));
            $last_payment = $layaway->payments()->orderBy('payment_date', 'desc')->first();

            $csv_data[] = [
                $layaway->layaway_number,
                $layaway->contact->name ?? '',
                $layaway->contact->mobile ?? '',
                $layaway->contact->email ?? '',
                $layaway->balance_due,
                $days_overdue,
                $layaway->payment_deadline,
                $last_payment ? $last_payment->payment_date : 'No payments'
            ];
        }

        $filename = 'overdue_layaways_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($csv_data) {
            $file = fopen('php://output', 'w');
            foreach ($csv_data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export customer history
     */
    private function exportCustomerHistory($request, $business_id)
    {
        if (!$request->has('contact_id')) {
            return redirect()->back()->with('error', 'Please select a customer');
        }

        $layaways = Layaway::where('business_id', $business_id)
            ->where('contact_id', $request->contact_id)
            ->with(['payments', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        $customer = Contact::find($request->contact_id);

        $csv_data = [];
        $csv_data[] = ['Customer History Report'];
        $csv_data[] = ['Customer Name: ' . $customer->name];
        $csv_data[] = ['Phone: ' . $customer->mobile];
        $csv_data[] = ['Email: ' . $customer->email];
        $csv_data[] = [];
        $csv_data[] = ['Layaway Number', 'Status', 'Total Amount', 'Paid Amount', 'Balance Due', 'Created Date', 'Payment Deadline'];

        foreach ($layaways as $layaway) {
            $csv_data[] = [
                $layaway->layaway_number,
                $layaway->status,
                $layaway->total_amount,
                $layaway->payments()->sum('amount'),
                $layaway->balance_due,
                $layaway->created_at,
                $layaway->payment_deadline
            ];
        }

        $filename = 'customer_layaway_history_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($csv_data) {
            $file = fopen('php://output', 'w');
            foreach ($csv_data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}