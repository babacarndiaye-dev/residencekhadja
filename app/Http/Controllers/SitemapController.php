<?php

namespace App\Http\Controllers;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = collect([
            ['loc' => route('home'),          'priority' => '1.0', 'freq' => 'weekly'],
            ['loc' => route('about'),         'priority' => '0.7', 'freq' => 'monthly'],
            ['loc' => route('rooms.index'),   'priority' => '0.9', 'freq' => 'weekly'],
            ['loc' => route('restaurant'),    'priority' => '0.8', 'freq' => 'monthly'],
            ['loc' => route('menu.public'),   'priority' => '0.6', 'freq' => 'weekly'],
            ['loc' => route('spaces'),        'priority' => '0.7', 'freq' => 'monthly'],
            ['loc' => route('events'),        'priority' => '0.8', 'freq' => 'monthly'],
            ['loc' => route('experiences'),   'priority' => '0.6', 'freq' => 'monthly'],
            ['loc' => route('gallery'),       'priority' => '0.5', 'freq' => 'monthly'],
            ['loc' => route('offers.index'),  'priority' => '0.8', 'freq' => 'weekly'],
            ['loc' => route('contact'),       'priority' => '0.6', 'freq' => 'yearly'],
            ['loc' => route('booking.start'), 'priority' => '0.9', 'freq' => 'weekly'],
        ]);

        foreach (config('rooms.items') as $room) {
            $urls->push([
                'loc' => route('rooms.show', $room['slug']),
                'priority' => '0.7',
                'freq' => 'monthly',
            ]);
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
