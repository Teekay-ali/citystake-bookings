<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailPreferencesController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'email_marketing' => 'required|boolean',
            'email_reminders' => 'required|boolean',
            'email_newsletters' => 'required|boolean',
        ]);

        $user = Auth::user();
        $user->update($validated);

        return back()->with('status', 'email-preferences-updated');
    }
}
