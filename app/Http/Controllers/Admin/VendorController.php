<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::with('createdBy')
            ->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('company', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $vendors = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Vendors/Index', [
            'vendors'    => $vendors,
            'categories' => Vendor::categories(),
            'filters'    => $request->only(['search', 'category', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Vendors/Form', [
            'categories' => Vendor::categories(),
            'vendor'     => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'company'             => 'nullable|string|max:255',
            'category'            => 'required|in:' . implode(',', array_keys(Vendor::categories())),
            'phone'               => 'required|string|max:20',
            'email'               => 'nullable|email|max:255',
            'address'             => 'nullable|string|max:500',
            'bank_name'           => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name'   => 'nullable|string|max:255',
            'rating'              => 'nullable|numeric|min:1|max:5',
            'notes'               => 'nullable|string|max:1000',
        ]);

        Vendor::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('manage.vendors.index')
            ->with('success', 'Vendor added successfully.');
    }

    public function edit(Vendor $vendor)
    {
        return Inertia::render('Admin/Vendors/Form', [
            'categories' => Vendor::categories(),
            'vendor'     => $vendor,
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'company'             => 'nullable|string|max:255',
            'category'            => 'required|in:' . implode(',', array_keys(Vendor::categories())),
            'phone'               => 'required|string|max:20',
            'email'               => 'nullable|email|max:255',
            'address'             => 'nullable|string|max:500',
            'bank_name'           => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name'   => 'nullable|string|max:255',
            'rating'              => 'nullable|numeric|min:1|max:5',
            'notes'               => 'nullable|string|max:1000',
            'is_active'           => 'boolean',
        ]);

        $vendor->update($validated);

        return redirect()->route('manage.vendors.index')
            ->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return back()->with('success', 'Vendor removed.');
    }
}
