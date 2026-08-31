<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;

class ServiceRequestController extends Controller
{
    public function index()
    {
        return view('admin.service.index', [
            'open' => ServiceRequest::with('location.venue')->open()->latest()->get(),
            'recentDone' => ServiceRequest::with('location.venue')->where('status', 'done')->latest('resolved_at')->take(15)->get(),
        ]);
    }

    public function acknowledge(ServiceRequest $serviceRequest)
    {
        $serviceRequest->update(['status' => 'acknowledged']);

        return back();
    }

    public function resolve(ServiceRequest $serviceRequest)
    {
        $serviceRequest->update(['status' => 'done', 'resolved_at' => now()]);

        return back()->with('status', 'Demande traitée.');
    }
}
