<?php

namespace App\Http\Controllers;

use App\Models\EventSpace;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about', [
            'experiences' => config('experiences'),
            'stats' => config('hotel.stats'),
        ]);
    }

    public function restaurant()
    {
        return view('pages.restaurant', [
            'venues' => config('dining.venues'),
            'saveurs' => config('dining.saveurs_du_senegal'),
        ]);
    }

    public function spaces()
    {
        return view('pages.spaces', [
            'leisure' => config('spaces.leisure'),
        ]);
    }

    public function events()
    {
        return view('pages.events', [
            'rooms' => config('spaces.meeting_rooms'),
            'services' => config('spaces.events_services'),
            // Formulaire de demande de devis.
            'spaces' => EventSpace::query()->where('is_active', true)
                ->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'eventTypes' => config('events.event_types'),
            'layouts' => config('events.layouts'),
            'catering' => config('events.catering_packages'),
            'equipment' => config('events.av_equipment'),
        ]);
    }

    public function gallery()
    {
        return view('pages.gallery', [
            'filters' => config('gallery.filters'),
            'photos' => config('gallery.photos'),
        ]);
    }

    public function experiences()
    {
        return view('pages.experiences', [
            'experiences' => config('experiences'),
        ]);
    }
}
