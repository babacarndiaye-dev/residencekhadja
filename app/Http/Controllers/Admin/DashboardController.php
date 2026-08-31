<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\HousekeepingTask;
use App\Models\LeaveRequest;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceTicket;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\ServiceRequest;
use App\Models\StockItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $arrivals = Reservation::with(['guest', 'roomCategory', 'room'])
            ->arrivingOn($today)->orderBy('arrival_time')->get();

        $departures = Reservation::with(['guest', 'roomCategory', 'room'])
            ->departingOn($today)->get();

        $inHouse = Reservation::with(['guest', 'roomCategory', 'room'])
            ->inHouse()->orderBy('check_out')->get();

        $sellableRooms = Room::where('is_active', true)->whereNotIn('status', ['hors_service'])->count();
        $roomsSoldToday = (int) Reservation::stayingOn($today)->sum('rooms_count');
        $occupancy = $sellableRooms > 0 ? round($roomsSoldToday / $sellableRooms * 100) : 0;

        $roomStatus = Room::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        // KPIs simples sur les séjours actifs.
        $activeStays = Reservation::stayingOn($today)->get();
        $adr = $activeStays->isNotEmpty()
            ? round($activeStays->sum(fn ($r) => $r->room_total / max(1, $r->nights())) / $activeStays->count())
            : 0;
        $revpar = $sellableRooms > 0
            ? round($activeStays->sum(fn ($r) => $r->room_total / max(1, $r->nights())) / $sellableRooms)
            : 0;

        return view('admin.dashboard', [
            'today' => $today,
            'arrivals' => $arrivals,
            'departures' => $departures,
            'inHouse' => $inHouse,
            'occupancy' => $occupancy,
            'roomsSoldToday' => $roomsSoldToday,
            'sellableRooms' => $sellableRooms,
            'roomStatus' => $roomStatus,
            'pendingCount' => Reservation::where('status', 'pending')->count(),
            'adr' => $adr,
            'revpar' => $revpar,
            'revenueToday' => (int) Payment::whereDate('received_at', $today)->sum('amount'),
            'bookedToday' => (int) Reservation::whereDate('created_at', $today)->sum('total'),
            'recent' => Reservation::with(['guest', 'roomCategory'])->latest()->take(8)->get(),
            'openOrders' => Order::open()->count(),
            'openServiceRequests' => ServiceRequest::open()->count(),
            'restaurantRevenueToday' => (int) Order::whereDate('created_at', $today)
                ->where('status', '!=', 'cancelled')->sum('total'),
            'hkTasksToday' => HousekeepingTask::forDate($today)->count(),
            'hkTasksDone' => HousekeepingTask::forDate($today)->whereIn('status', ['done', 'inspected'])->count(),
            'maintenanceOpen' => MaintenanceTicket::open()->count(),
            'maintenanceCritical' => MaintenanceTicket::open()->where('priority', 'critical')->count(),
            'preventiveDue' => MaintenancePlan::due()->count(),
            'treasury' => (int) FinanceAccount::where('is_active', true)->get()->sum->balance(),
            'monthIncome' => (int) FinanceTransaction::where('direction', 'income')->whereMonth('operation_date', $today->month)->whereYear('operation_date', $today->year)->sum('amount'),
            'monthExpense' => (int) FinanceTransaction::where('direction', 'expense')->whereMonth('operation_date', $today->month)->whereYear('operation_date', $today->year)->sum('amount'),
            'stockValue' => (int) StockItem::active()->get()->sum->stockValue(),
            'stockLow' => StockItem::active()->get()->filter->isBelowThreshold()->count(),
            'headcount' => Employee::active()->count(),
            'pendingLeave' => LeaveRequest::pending()->count(),
        ]);
    }
}
