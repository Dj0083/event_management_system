<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)], 'phone' => ['nullable', 'string', 'max:40']]);
        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'string', 'min:12', 'confirmed']]);
        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password changed.');
    }
}
