<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ScopedByBuilding;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingCalendarController extends Controller
{
    use ScopedByBuilding;

    public function index(Request $request)
    {
        $buildings = $this->accessibleBuildings()->get();

        $user = auth()->user();
        $bookings = Booking::with(['building', 'unitType', 'unit'])
            ->when(!$user->hasGlobalAccess(), function ($query) use ($user) {
                $query->whereIn('building_id', $user->accessibleBuildingIds() ?? []);
            })
            ->when($request->building_id, function ($query, $buildingId) {
                $query->where('building_id', $buildingId);
            })
            ->whereIn('status', ['confirmed', 'pending', 'completed'])
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'title' => $booking->guest_name . ' - ' . $booking->unitType->name,
                    'start' => $booking->check_in->format('Y-m-d'),
                    'end' => $booking->check_out->format('Y-m-d'),
                    'backgroundColor' => $this->getBookingColor($booking),
                    'borderColor' => $this->getBookingColor($booking),
                    'extendedProps' => [
                        'booking_reference' => $booking->booking_reference,
                        'guest_name' => $booking->guest_name,
                        'property' => $booking->building->name,
                        'unit_type' => $booking->unitType->name,
                        'unit_number' => $booking->unit->unit_number,
                        'guests' => $booking->guests,
                        'status' => $booking->status,
                        'payment_status' => $booking->payment_status,
                        'total_amount' => $booking->total_amount,
                    ],
                ];
            });

        return Inertia::render('Admin/Bookings/Calendar', [
            'bookings' => $bookings,
            'buildings' => $buildings,
            'filters' => [
                'building_id' => $request->building_id,
            ],
        ]);
    }

    private function getBookingColor($booking)
    {
        if ($booking->status === 'cancelled') {
            return '#EF4444'; // Red
        } elseif ($booking->payment_status === 'pending') {
            return '#F59E0B'; // Yellow/Orange
        } elseif ($booking->status === 'completed') {
            return '#6B7280'; // Gray
        } else {
            return '#10B981'; // Green (confirmed)
        }
    }
}
