<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\BookingsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BookingExportController extends Controller
{
    public function export(Request $request)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        // Inject scoped building IDs into the request so BookingsExport can use them
        $user = auth()->user();
        $accessibleIds = $user->hasGlobalAccess()
            ? null
            : $user->accessibleBuildingIds();

        // Validate building_id is within scope if provided
        if ($request->filled('building_id') && $accessibleIds !== null) {
            if (!in_array((int) $request->building_id, $accessibleIds)) {
                abort(403);
            }
        }

        $filename = 'cs-bookings-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new BookingsExport($request, $accessibleIds), $filename);
    }
}
