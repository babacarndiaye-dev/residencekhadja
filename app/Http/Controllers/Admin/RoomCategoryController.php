<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\RoomCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoomCategoryController extends Controller
{
    public function index()
    {
        return view('admin.room-categories.index', [
            'categories' => RoomCategory::query()->ordered()
                ->withCount('rooms')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('admin.room-categories.form', [
            'category' => new RoomCategory([
                'category' => 'chambre',
                'capacity' => 2,
                'is_active' => true,
                'sort_order' => (int) RoomCategory::max('sort_order') + 1,
            ]),
        ]);
    }

    public function edit(RoomCategory $category)
    {
        return view('admin.room-categories.form', ['category' => $category]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $hotelId = Hotel::current()->id;

        $data['hotel_id'] = $hotelId;
        $data['slug'] = $this->uniqueSlug($data['name'], $hotelId);
        $data['images'] = $this->handleImages($request, []);

        $category = RoomCategory::create($data);
        AuditLog::record('room_category.created', $category);

        return redirect()->route('admin.room_categories.index')->with('status', "Catégorie « {$category->name} » créée.");
    }

    public function update(Request $request, RoomCategory $category)
    {
        $data = $this->validated($request, $category);
        $data['images'] = $this->handleImages($request, $category->images ?? [], $request->input('remove_images', []));

        $category->update($data);
        AuditLog::record('room_category.updated', $category);

        return redirect()->route('admin.room_categories.index')->with('status', "Catégorie « {$category->name} » mise à jour.");
    }

    public function destroy(RoomCategory $category)
    {
        if ($category->rooms()->exists() || $category->reservations()->exists()) {
            return back()->withErrors(['category' => "« {$category->name} » a des chambres ou un historique de réservations : désactivez-la plutôt que de la supprimer."]);
        }

        foreach ($category->images ?? [] as $path) {
            if (! Str::startsWith($path, ['http://', 'https://', '/'])) {
                Storage::disk('public')->delete($path);
            }
        }

        $name = $category->name;
        $category->delete();
        AuditLog::record('room_category.deleted', $category, ['name' => $name]);

        return redirect()->route('admin.room_categories.index')->with('status', "Catégorie « {$name} » supprimée.");
    }

    private function validated(Request $request, ?RoomCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(['chambre', 'suite'])],
            'price' => ['required', 'integer', 'min:0'],
            'size' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:12'],
            'bed' => ['nullable', 'string', 'max:80'],
            'view' => ['nullable', 'string', 'max:80'],
            'short' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amenities' => ['nullable', 'string', 'max:600'],
            'featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        unset($data['photos']);
        $data['amenities'] = collect(explode(',', (string) ($data['amenities'] ?? '')))
            ->map(fn ($s) => trim($s))->filter()->values()->all();
        $data['featured'] = $request->boolean('featured');
        $data['is_active'] = $request->boolean('is_active');
        $data['size'] = $data['size'] ?? 0;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    /** @param  array<int, string>  $current */
    private function handleImages(Request $request, array $current, array $remove = []): array
    {
        $kept = array_values(array_filter($current, fn ($p) => ! in_array($p, $remove, true)));

        foreach ($remove as $path) {
            if ($path && ! Str::startsWith($path, ['http://', 'https://', '/'])) {
                Storage::disk('public')->delete($path);
            }
        }

        foreach ($request->file('photos', []) as $photo) {
            $kept[] = $photo->store('room-categories', 'public');
        }

        return $kept;
    }

    private function uniqueSlug(string $name, int $hotelId): string
    {
        $base = Str::slug($name) ?: 'categorie';
        $slug = $base;
        $i = 2;
        while (RoomCategory::where('hotel_id', $hotelId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
