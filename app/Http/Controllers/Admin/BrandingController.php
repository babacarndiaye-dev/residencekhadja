<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Branding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    /** Les deux logos gérés : champ du formulaire => libellé. */
    private const SLOTS = [
        'logo' => 'logo principal',
        'logo_mono' => 'logo monochrome',
    ];

    public function edit()
    {
        return view('admin.branding.edit', [
            'paths' => Branding::paths(),
            'urls' => [
                'logo' => Branding::logo(),
                'logo_mono' => Branding::logoMono(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'logo' => ['nullable', 'file', 'mimes:svg,png,webp,jpg,jpeg', 'max:1024'],
            'logo_mono' => ['nullable', 'file', 'mimes:svg,png,webp,jpg,jpeg', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_logo_mono' => ['nullable', 'boolean'],
        ], [], self::SLOTS);

        $current = Branding::paths();
        $values = [];

        foreach (array_keys(self::SLOTS) as $slot) {
            if ($request->boolean('remove_'.$slot) && ! empty($current[$slot])) {
                Storage::disk('public')->delete($current[$slot]);
                $values[$slot.'_path'] = null;
            }

            if ($request->hasFile($slot)) {
                if (! empty($current[$slot])) {
                    Storage::disk('public')->delete($current[$slot]);
                }
                $values[$slot.'_path'] = $request->file($slot)->store('branding', 'public');
            }
        }

        if ($values === []) {
            return back()->with('status', 'Aucune modification.');
        }

        Branding::save($values, $request->user()->id);
        AuditLog::record('branding.updated', null, ['keys' => array_keys($values)]);

        return back()->with('status', 'Logo mis à jour — visible partout (vitrine, back-office, documents).');
    }
}
