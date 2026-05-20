<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Image;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Upload one or more images for a Building or UnitType.
     * POST /manage/images/{type}/{id}
     * type = 'building' | 'unit-type'
     */
    public function store(Request $request, string $type, int $id)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

        $request->validate([
            'images'   => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB per image
        ]);

        $model = $this->resolveModel($type, $id);

        $existingCount = $model->images()->count();
        $uploaded = [];

        foreach ($request->file('images') as $index => $file) {
            $path = $file->store('property-images', 'public');

            $image = $model->images()->create([
                'image_path' => $path,
                'sort_order' => $existingCount + $index + 1,
                'is_primary'  => $existingCount === 0 && $index === 0, // first ever image becomes primary
            ]);

            $uploaded[] = [
                'id'         => $image->id,
                'image_path' => $path,
                'url'        => Storage::disk('public')->url($path),
                'is_primary' => $image->is_primary,
                'sort_order' => $image->sort_order,
            ];
        }

        return response()->json([
            'message' => count($uploaded) . ' image(s) uploaded successfully.',
            'images'  => $uploaded,
        ]);
    }

    /**
     * Delete a single image.
     * DELETE /manage/images/{image}
     */
    public function destroy(Image $image)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

        $wasPrimary = $image->is_primary;
        $model = $image->imageable;

        // Delete file from disk
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        // If deleted image was primary, promote the next image
        if ($wasPrimary && $model) {
            $next = $model->images()->orderBy('sort_order')->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return response()->json(['message' => 'Image deleted.']);
    }

    /**
     * Set an image as the primary image for its parent.
     * PATCH /manage/images/{image}/primary
     */
    public function setPrimary(Image $image)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

        $model = $image->imageable;

        // Clear existing primary on siblings
        $model->images()->update(['is_primary' => false]);

        $image->update(['is_primary' => true]);

        return response()->json(['message' => 'Primary image updated.']);
    }

    /**
     * Reorder images.
     * PATCH /manage/images/reorder
     * Body: { "order": [{ "id": 1, "sort_order": 1 }, ...] }
     */
    public function reorder(Request $request)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

        $request->validate([
            'order'              => 'required|array|min:1',
            'order.*.id'         => 'required|integer|exists:images,id',
            'order.*.sort_order' => 'required|integer|min:1',
        ]);

        foreach ($request->order as $item) {
            Image::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Order saved.']);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveModel(string $type, int $id): Building|UnitType
    {
        return match($type) {
            'building'  => Building::findOrFail($id),
            'unit-type' => UnitType::findOrFail($id),
            default     => abort(404, 'Unknown image target type.'),
        };
    }
}
