<?php

namespace App\Http\Controllers;

class OfferController extends Controller
{
    public function index()
    {
        return view('pages.offers', [
            'offers' => config('offers'),
        ]);
    }
}
