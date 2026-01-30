<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIResponse;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'phone' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:255',

            'profile.insurer_name' => 'nullable|string|max:255',
            'profile.policy_number' => 'nullable|string|max:255',
            'profile.renewal_date' => 'nullable',
            'profile.boat_registration' => 'nullable|string|max:255',
            'profile.boat_length' => 'nullable|string|max:255',
            'profile.boat_maker' => 'nullable|string|max:255',
            'profile.boat_color' => 'nullable|string|max:255',
            'profile.emergency_contact_name' => 'nullable|string|max:255',
            'profile.emergency_contact_number' => 'nullable|string|max:255',

            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'boat_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'insurance_certificate' => 'nullable|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        DB::beginTransaction();

        try {
            // Merge existing user data with new data
            $userData = [
                'name' => $validated['name'] ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
                'phone' => $validated['phone'] ?? $user->phone,
                'category' => $validated['category'] ?? $user->category,
            ];

            // Handle password separately
            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $user->update($userData);

            // Handle profile picture update
            if ($request->hasFile('profile_picture')) {
                $user->clearMediaCollection('profile_picture');
                $user->addMedia($request->file('profile_picture'))->toMediaCollection('profile_picture');
            }
            if ($request->hasFile('boat_photo')) {
                $user->clearMediaCollection('boat_photo');
                $user->addMedia($request->file('boat_photo'))
                    ->toMediaCollection('boat_photo');
            }
            if ($request->hasFile('insurance_certificate')) {
                $user->clearMediaCollection('insurance_certificate');
                $user->addMedia($request->file('insurance_certificate'))
                    ->toMediaCollection('insurance_certificate');
            }

            // Handle profile update
            if (isset($validated['profile'])) {
                $existingProfile = $user->profile;

                $profileData = [
                    'insurer_name' => $validated['profile']['insurer_name'] ?? $existingProfile->insurer_name ?? null,
                    'policy_number' => $validated['profile']['policy_number'] ?? $existingProfile->policy_number ?? null,
                    'renewal_date' => $validated['profile']['renewal_date'] ?? $existingProfile->renewal_date ?? null,
                    'boat_registration' => $validated['profile']['boat_registration'] ?? $existingProfile->boat_registration ?? null,
                    'boat_length' => $validated['profile']['boat_length'] ?? $existingProfile->boat_length ?? null,
                    'boat_maker' => $validated['profile']['boat_maker'] ?? $existingProfile->boat_maker ?? null,
                    'boat_color' => $validated['profile']['boat_color'] ?? $existingProfile->boat_color ?? null,
                    'emergency_contact_name' => $validated['profile']['emergency_contact_name'] ?? $existingProfile->emergency_contact_name ?? null,
                    'emergency_contact_number' => $validated['profile']['emergency_contact_number'] ?? $existingProfile->emergency_contact_number ?? null,
                ];

                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileData
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

    public function myTeam($id = null)
    {
        $user = Auth::user();

        $teamsQuery = $user->team();

        if ($id) {
            $teamsQuery->wherePivot('event_id', $id);
        }

        $teams = $teamsQuery->with(['anglers' => function ($query) use ($id) {
            if ($id) {
                $query->wherePivot('event_id', $id);
            }
        }])->get();

        return APIResponse::success('Team Fetched Successfully', [
            'team' => $teams,
        ]);
    }



}
