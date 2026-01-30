<?php

namespace App\Exports;

use App\Models\EventCatch;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EventCatchExport implements FromCollection, WithHeadings, WithMapping , ShouldAutoSize
{
    protected $eventId;

    public function __construct($eventId)
    {
        $this->eventId = $eventId;
    }

    public function collection()
    {
        return EventCatch::where('event_id', $this->eventId)
            ->with(['angler', 'specie', 'team'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'Team Number',
            'Team Name',
            'Angler Number',
            'Angler Name',
            'Category',
            'Species',
            'Fork Length',
            'Points',
            'Catch Time',
            'Fish Photo',
            'Extra Fish Photo',
        ];
    }

    public function map($catch): array
    {
        $mediaItems = $catch->getMedia('event_fish_images');

        $fishPhoto = $mediaItems->get(0)?->getFullUrl() ?? '';

        $extraFishPhotos = $mediaItems->slice(1)->pluck('original_url')->implode(', ');

        return [
            $catch->team->team_uid ?? '',
            $catch->team->name ?? '',
            $catch->angler->angler_uid ?? '',
            $catch->angler->name ?? '',
            $catch->angler->category ?? '',
            $catch->specie->name ?? '',
            $catch->fork_length,
            $catch->points,
            Carbon::parse($catch->catch_timestamp)->format('d M Y h:i A'),
            $fishPhoto,
            $extraFishPhotos,
        ];
    }
}
