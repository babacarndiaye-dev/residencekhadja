<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Splash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SplashScreenController extends Controller
{
    public function edit()
    {
        return view('admin.splash.edit', ['s' => Splash::view()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'animation' => ['required', Rule::in(Splash::animations())],
            'background_from' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background_to' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'glow' => ['nullable', 'boolean'],
            'welcome_text' => ['nullable', 'string', 'max:40'],
            'hotel_name' => ['nullable', 'string', 'max:80'],
            'signature' => ['nullable', 'string', 'max:120'],
            'duration_seconds' => ['required', 'numeric', 'min:1', 'max:6'],
            'logo' => ['nullable', 'file', 'mimes:svg,png,webp,jpg,jpeg', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [], [
            'background_from' => 'couleur de fond (départ)',
            'background_to' => 'couleur de fond (fin)',
            'duration_seconds' => 'durée',
        ]);

        $current = Splash::all();

        $values = [
            'enabled' => $request->boolean('enabled'),
            'animation' => $data['animation'],
            'background_from' => strtolower($data['background_from']),
            'background_to' => strtolower($data['background_to']),
            'glow' => $request->boolean('glow'),
            'welcome_text' => $data['welcome_text'] ?? '',
            'hotel_name' => $data['hotel_name'] ?? '',
            'signature' => $data['signature'] ?? '',
            'duration_ms' => (int) round($data['duration_seconds'] * 1000),
        ];

        if ($request->boolean('remove_logo') && ! empty($current['logo_path'])) {
            Storage::disk('public')->delete($current['logo_path']);
            $values['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if (! empty($current['logo_path'])) {
                Storage::disk('public')->delete($current['logo_path']);
            }
            $values['logo_path'] = $request->file('logo')->store('splash', 'public');
        }

        Splash::save($values, $request->user()->id);
        AuditLog::record('splash.updated', null, ['enabled' => $values['enabled'], 'animation' => $values['animation']]);

        return back()->with('status', 'Écran d’accueil enregistré.');
    }

    /** Aperçu isolé — rejoué à volonté depuis l'éditeur (surcharges via query string). */
    public function preview(Request $request)
    {
        $s = Splash::view();

        foreach (['welcome_text', 'hotel_name', 'signature', 'animation', 'background_from', 'background_to'] as $key) {
            if ($request->filled($key)) {
                $s[$key] = (string) $request->query($key);
            }
        }
        if ($request->filled('glow')) {
            $s['glow'] = $request->boolean('glow');
        }
        if ($request->filled('duration_ms')) {
            $s['duration_ms'] = max(1000, min(6000, (int) $request->query('duration_ms')));
        }
        if (! in_array($s['animation'], Splash::animations(), true)) {
            $s['animation'] = 'cinematic';
        }

        return response()
            ->view('admin.splash.preview', ['s' => $s])
            ->header('Cache-Control', 'no-store');
    }
}
