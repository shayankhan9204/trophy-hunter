<?php

namespace App\Imports;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;

class TeamMembersImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        if (strtolower(trim($row[0])) === 'team name') return null;

        if (count($row) < 5 || empty($row[3]) || empty($row[4])) return null;

        $teamName = trim($row[0]);
        $name = trim($row[1]);
        $category = trim($row[2]);
        $email = strtolower(trim($row[3]));
        $phone = trim($row[4]);

        $team = Team::firstOrCreate(['name' => $teamName]);

        $user = User::where('email', $email)
            ->where('phone', $phone)
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make('12345678'),
                'team_id' => $team->id,
                'category' => $category,
            ]);
        } else {
            $user->update([
                'name' => $name,
                'team_id' => $team->id,
                'category' => $category,
            ]);
        }

        if (!$user->hasRole('angler')) {
            $user->assignRole('angler');
        }

        return $user;
    }

}
