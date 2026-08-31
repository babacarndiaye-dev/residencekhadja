<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\RatePlan;
use App\Models\RoomCategory;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            'categories' => RoomCategory::query()->ordered()->withCount('rooms')->get(),
            'ratePlans' => RatePlan::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function updateCategory(Request $request, RoomCategory $category)
    {
        $data = $request->validate([
            'price' => ['required', 'integer', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10'],
            'featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'price' => $data['price'],
            'capacity' => $data['capacity'],
            'featured' => $request->boolean('featured'),
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLog::record('category.updated', $category, $data);

        return back()->with('status', "Catégorie « {$category->name} » mise à jour.");
    }

    public function updateRatePlan(Request $request, RatePlan $ratePlan)
    {
        $data = $request->validate([
            'multiplier' => ['required', 'numeric', 'min:0.1', 'max:3'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $ratePlan->update([
            'multiplier' => $data['multiplier'],
            'note' => $data['note'] ?? $ratePlan->note,
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLog::record('rate_plan.updated', $ratePlan, $data);

        return back()->with('status', "Plan tarifaire « {$ratePlan->name} » mis à jour.");
    }
}
