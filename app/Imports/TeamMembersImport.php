<?php

namespace App\Imports;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;

class TeamMembersImport implements ToModel
{
    protected $eventId;
    protected array $allowedCategories = ['adult','junior','senior','female','veteran']; 
    protected string $defaultCategory = 'adult';


    public function __construct($eventId)
    {
        $this->eventId = $eventId;
    }

    protected function normalizeCategory(?string $category): string
    {
        $category = strtolower(trim((string) $category));

        return in_array($category, $this->allowedCategories, true)
            ? $category
            : $this->defaultCategory;
    }

    public function model(array $row)
    {
        if (strtolower(trim($row[0])) === 'team name') return null;
        if (count($row) < 6 || empty($row[4]) || empty($row[5])) return null;

        $teamName = trim($row[0]);
        $angularNo = trim($row[1]);
        $name = trim($row[2]);
        $category = $this->normalizeCategory($row[3]);
        $email = strtolower(trim($row[4]));
        $phone = trim($row[5]);

        $team = Team::firstOrCreate(['name' => $teamName]);

        $user = User::firstOrNew(['email' => $email]);

        $user->name = $name ?? null;
        $user->category = $category;
        $user->phone = $phone;
        if (!$user->exists) {
            $user->password = Hash::make($phone);
            $user->plain_password = $phone;
        }
        $user->save();

        $alreadyInEvent = \DB::table('event_team_user')
            ->where('event_id', $this->eventId)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->exists();

        if (!$alreadyInEvent) {
            $user->team()->attach($team->id, [
                'event_id' => $this->eventId,
                'angular_uid' => $angularNo ?: 'AGL-' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
            ]);
        }

        if (!$user->hasRole('angler')) {
            $user->assignRole('angler');
        }

        return $user;
    }

}
