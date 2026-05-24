<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\PaymentApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Upload documents for a model.
     * POST /manage/documents/{type}/{id}
     * type = 'payment-approval' (extendable to other models)
     */
    public function store(Request $request, string $type, int $id)
    {
        abort_unless(auth()->user()->can('manage-payment-approvals'), 403);

        $request->validate([
            'documents'   => 'required|array|min:1|max:5',
            'documents.*' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ]);

        $model = $this->resolveModel($type, $id);
        $existingCount = $model->documents()->count();
        $uploaded = [];

        foreach ($request->file('documents') as $index => $file) {
            $path = $file->store("documents/{$type}", 'public');

            $doc = $model->documents()->create([
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'file_size'     => $file->getSize(),
                'uploaded_by'   => auth()->id(),
                'sort_order'    => $existingCount + $index + 1,
            ]);

            $uploaded[] = [
                'id'             => $doc->id,
                'url'            => $doc->url,
                'original_name'  => $doc->original_name,
                'mime_type'      => $doc->mime_type,
                'is_image'       => $doc->is_image,
                'formatted_size' => $doc->formatted_size,
            ];
        }

        return response()->json([
            'message'   => count($uploaded) . ' document(s) uploaded.',
            'documents' => $uploaded,
        ]);
    }

    /**
     * Delete a document.
     * DELETE /manage/documents/{document}
     */
    public function destroy(Document $document)
    {
        abort_unless(auth()->user()->can('manage-payment-approvals'), 403);

        // Only uploader or super-admin can delete
        abort_unless(
            $document->uploaded_by === auth()->id() || auth()->user()->hasRole('super-admin'),
            403
        );

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted.']);
    }

    private function resolveModel(string $type, int $id): PaymentApproval
    {
        return match($type) {
            'payment-approval' => PaymentApproval::findOrFail($id),
            default            => abort(404, 'Unknown document target type.'),
        };
    }
}
