<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'phone' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:255',

            'profile.insurer_name' => 'nullable|string|max:255',
            'profile.policy_number' => 'nullable|string|max:255',
            'profile.renewal_date' => 'nullable|date',
            'profile.boat_registration' => 'nullable|string|max:255',
            'profile.boat_length' => 'nullable|string|max:255',
            'profile.boat_maker' => 'nullable|string|max:255',
            'profile.boat_color' => 'nullable|string|max:255',
            'profile.emergency_contact_name' => 'nullable|string|max:255',
            'profile.emergency_contact_number' => 'nullable|string|max:255',

            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $user->update(array_filter($validated, fn($key) => $key !== 'profile', ARRAY_FILTER_USE_KEY));

            if ($request->hasFile('profile_picture')) {
                $user->clearMediaCollection('profile_picture');
                $user->addMedia($request->file('profile_picture'))->toMediaCollection('profile_picture');
            }

            if (isset($validated['profile'])) {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $validated['profile']
                );
            }

            DB::commit();

            return APIResponse::success('Profile updated successfully', [
                'user' => $user->load('profile')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return APIResponse::error('Update failed', ['error' => $e->getMessage()], 500);
        }
    }
}
