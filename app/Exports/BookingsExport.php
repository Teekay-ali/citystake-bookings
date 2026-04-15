<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Headable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class BookingsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{

    protected $request;
    protected ?array $accessibleBuildingIds;

    public function __construct(Request $request, ?array $accessibleBuildingIds = null)
    {
        $this->request = $request;
        $this->accessibleBuildingIds = $accessibleBuildingIds;
    }

    public function query()
    {
        $query = Booking::with(['building', 'unitType', 'unit', 'user']);

        // Scope to accessible buildings
        if ($this->accessibleBuildingIds !== null) {
            $query->whereIn('building_id', $this->accessibleBuildingIds);
        }

        // Apply filters
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }

        if ($this->request->filled('payment_status')) {
            $query->where('payment_status', $this->request->payment_status);
        }

        if ($this->request->filled('building_id')) {
            $query->where('building_id', $this->request->building_id);
        }

        if ($this->request->filled('date_from')) {
            $query->whereDate('check_in', '>=', $this->request->date_from);
        }

        if ($this->request->filled('date_to')) {
            $query->whereDate('check_out', '<=', $this->request->date_to);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Booking ID',
            'Reference',
            'Guest Name',
            'Guest Email',
            'Guest Phone',
            'Property',
            'Unit Type',
            'Unit Number',
            'Check-in',
            'Check-out',
            'Nights',
            'Guests',
            'Subtotal',
            'Cleaning Fee',
            'Service Charge',
            'Total Amount',
            'Payment Status',
            'Payment Method',
            'Booking Status',
            'Created At',
            'Special Requests',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->id,
            $booking->booking_reference,
            $booking->guest_name,
            $booking->guest_email,
            $booking->guest_phone,
            $booking->building->name ?? 'N/A',
            $booking->unitType->name ?? 'N/A',
            $booking->unit->unit_number ?? 'N/A',
            $booking->check_in->format('Y-m-d'),
            $booking->check_out->format('Y-m-d'),
            $booking->nights,
            $booking->guests,
            '₦' . number_format($booking->subtotal, 2),
            '₦' . number_format($booking->cleaning_fee, 2),
            '₦' . number_format($booking->service_charge, 2),
            '₦' . number_format($booking->total_amount, 2),
            ucfirst($booking->payment_status),
            $booking->payment_method ? ucfirst(str_replace('_', ' ', $booking->payment_method)) : 'N/A',
            ucfirst($booking->status),
            $booking->created_at->format('Y-m-d H:i'),
            $booking->special_requests ?? 'None',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB']
                ]
            ],
        ];
    }
}
