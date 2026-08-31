@props(['status', 'label' => null])

@php
    $map = [
        // Réservations
        'pending' => 'bg-laiton-100 text-laiton-600',
        'confirmed' => 'bg-nuit-100 text-nuit-700',
        'checked_in' => 'bg-terracotta-100 text-terracotta-700',
        'checked_out' => 'bg-sable-200 text-nuit-600',
        'cancelled' => 'bg-red-100 text-red-700',
        'no_show' => 'bg-red-100 text-red-700',
        // Chambres
        'libre' => 'bg-emerald-100 text-emerald-700',
        'propre' => 'bg-emerald-100 text-emerald-700',
        'occupee' => 'bg-terracotta-100 text-terracotta-700',
        'sale' => 'bg-laiton-100 text-laiton-600',
        'en_nettoyage' => 'bg-nuit-100 text-nuit-700',
        'controle' => 'bg-nuit-100 text-nuit-700',
        'bloquee' => 'bg-red-100 text-red-700',
        'hors_service' => 'bg-red-100 text-red-700',
        // Commandes
        'new' => 'bg-laiton-100 text-laiton-600',
        'preparing' => 'bg-nuit-100 text-nuit-700',
        'ready' => 'bg-emerald-100 text-emerald-700',
        'served' => 'bg-terracotta-100 text-terracotta-700',
        'completed' => 'bg-sable-200 text-nuit-600',
        // Paiement commande
        'unpaid' => 'bg-red-100 text-red-700',
        'paid' => 'bg-emerald-100 text-emerald-700',
        'charged_to_room' => 'bg-nuit-100 text-nuit-700',
        // Housekeeping
        'in_progress' => 'bg-nuit-100 text-nuit-700',
        'done' => 'bg-emerald-100 text-emerald-700',
        'inspected' => 'bg-emerald-100 text-emerald-700',
        'blocked' => 'bg-red-100 text-red-700',
        // Maintenance — statuts
        'assigned' => 'bg-nuit-100 text-nuit-700',
        'on_hold' => 'bg-laiton-100 text-laiton-600',
        'resolved' => 'bg-emerald-100 text-emerald-700',
        'closed' => 'bg-sable-200 text-nuit-600',
        // Maintenance — priorités
        'low' => 'bg-sable-200 text-nuit-600',
        'high' => 'bg-laiton-100 text-laiton-600',
        'critical' => 'bg-red-100 text-red-700',
        // Équipement
        'operational' => 'bg-emerald-100 text-emerald-700',
        'degraded' => 'bg-laiton-100 text-laiton-600',
        'out_of_service' => 'bg-red-100 text-red-700',
        // Achats
        'draft' => 'bg-sable-200 text-nuit-600',
        'submitted' => 'bg-laiton-100 text-laiton-600',
        'approved' => 'bg-emerald-100 text-emerald-700',
        'ordered' => 'bg-nuit-100 text-nuit-700',
        'partially_received' => 'bg-laiton-100 text-laiton-600',
        'received' => 'bg-emerald-100 text-emerald-700',
        'partially_paid' => 'bg-laiton-100 text-laiton-600',
        // RH — employé / congé / paie / shift
        'on_leave' => 'bg-laiton-100 text-laiton-600',
        'suspended' => 'bg-red-100 text-red-700',
        'terminated' => 'bg-sable-200 text-nuit-500',
        'rejected' => 'bg-red-100 text-red-700',
        'planned' => 'bg-nuit-100 text-nuit-700',
        'confirmed' => 'bg-emerald-100 text-emerald-700',
        'swapped' => 'bg-laiton-100 text-laiton-600',
    ];
    $cls = $map[$status] ?? 'bg-sable-200 text-nuit-600';
    $fallback = \App\Models\Reservation::STATUSES[$status]
        ?? \App\Models\Room::STATUSES[$status]
        ?? \App\Models\Order::STATUSES[$status]
        ?? \App\Models\HousekeepingTask::STATUSES[$status]
        ?? \App\Models\PurchaseOrder::STATUSES[$status]
        ?? \App\Models\SupplierInvoice::STATUSES[$status]
        ?? \App\Models\LeaveRequest::STATUSES[$status]
        ?? \App\Models\PayrollRun::STATUSES[$status]
        ?? \App\Models\Shift::STATUSES[$status]
        ?? config('hr.employment_statuses')[$status]
        ?? config('maintenance.ticket_statuses')[$status]
        ?? config('maintenance.ticket_priorities')[$status]
        ?? $status;
@endphp

<span {{ $attributes->class("inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold $cls") }}>
    {{ $label ?? $fallback }}
</span>
