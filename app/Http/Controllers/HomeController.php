<?php

namespace App\Http\Controllers;

use App\Models\RoomCategory;
use App\Services\Satisfaction;

class HomeController extends Controller
{
    public function index()
    {
        return view('pages.home', [
            'rooms' => RoomCategory::query()->active()->where('featured', true)->ordered()->take(3)->get(),
            'saveurs' => collect(config('dining.saveurs_du_senegal'))->take(4)->values(),
            'offers' => collect(config('offers'))->take(3)->values(),
            'experiences' => collect(config('experiences.items'))->take(6)->values(),
            'leisure' => collect(config('spaces.leisure'))->take(4)->values(),
            // Avis publiés depuis le module Satisfaction (repli sur config/reviews.php).
            'reviews' => Satisfaction::vitrineReviews(),
            'gallery' => collect(config('gallery.photos'))->take(8)->values(),
        ]);
    }
}
