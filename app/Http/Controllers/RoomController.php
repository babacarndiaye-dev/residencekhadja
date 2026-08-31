<?php

namespace App\Http\Controllers;

use App\Models\RoomCategory;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('categorie');

        $rooms = RoomCategory::query()
            ->active()
            ->ordered()
            ->when(
                $category && array_key_exists($category, config('rooms.categories')),
                fn ($q) => $q->where('category', $category)
            )
            ->get();

        return view('pages.rooms.index', [
            'categories' => config('rooms.categories'),
            'active' => $category,
            'rooms' => $rooms,
        ]);
    }

    public function show(string $slug)
    {
        $room = RoomCategory::query()->active()->where('slug', $slug)->first();

        abort_if(! $room, 404);

        $others = RoomCategory::query()
            ->active()
            ->where('id', '!=', $room->id)
            ->inRandomOrder()
            ->limit(2)
            ->get();

        return view('pages.rooms.show', compact('room', 'others'));
    }
}
