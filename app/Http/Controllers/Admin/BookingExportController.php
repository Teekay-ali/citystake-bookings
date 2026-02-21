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
        $filename = 'cs-bookings-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new BookingsExport($request), $filename);
    }
}
