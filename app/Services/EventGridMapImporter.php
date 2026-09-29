<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventLocationArea;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class EventGridMapImporter
{
    /**
     * @return array{imported: int, errors: string[]}
     */
    public function importFromCsv(Event $event, UploadedFile $file, bool $replaceExisting = true): array
    {
        $rows = $this->readCsvRows($file);

        if (count($rows) < 2) {
            throw new \InvalidArgumentException('The uploaded CSV is empty or does not contain data rows.');
        }

        $headerMap = $this->buildHeaderMap($rows[0]);
        $columnIndexes = $this->resolveColumnIndexes($headerMap);

        if ($replaceExisting) {
            $event->locationAreas()->delete();
        }

        $imported = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $line => $row) {
            $rowNumber = $line + 2;
            $parsed = $this->parseRow($row, $columnIndexes, $rowNumber, $errors);

            if ($parsed === null) {
                continue;
            }

            EventLocationArea::create(array_merge(['event_id' => $event->id], $parsed));
            $imported++;
        }

        if ($imported === 0 && empty($errors)) {
            throw new \InvalidArgumentException('No valid location area rows were found in the CSV.');
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    private function readCsvRows(UploadedFile $file): array
    {
        $path = $file->getRealPath() ?: $file->getPathname();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to read the uploaded CSV file.');
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function buildHeaderMap(array $headerRow): array
    {
        $headerMap = [];

        foreach ($headerRow as $index => $header) {
            $headerMap[$this->normalizeHeader($header)] = $index;
        }

        return $headerMap;
    }

    private function resolveColumnIndexes(array $headerMap): array
    {
        $find = function (array $candidates) use ($headerMap) {
            foreach ($candidates as $candidate) {
                if (array_key_exists($candidate, $headerMap)) {
                    return $headerMap[$candidate];
                }
            }

            return null;
        };

        $columns = [
            'location_reference' => $find(['location_reference', 'location', 'reference', 'location_ref']),
            'top_left' => $find(['top_left_coord', 'top_left', 'top_left_coordinates']),
            'top_right' => $find(['top_right_coord', 'top_right', 'top_right_coordinates']),
            'bottom_right' => $find(['bottom_right_coord', 'bottom_right', 'bottom_right_coordinates']),
            'bottom_left' => $find(['bottom_left_coord', 'bottom_left', 'bottom_left_coordinates']),
        ];

        $missing = [];

        foreach ($columns as $name => $index) {
            if ($index === null) {
                $missing[] = str_replace('_', ' ', $name);
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required CSV columns. Expected: Location Reference, Top Left Coord, Top Right Coord, Bottom Right Coord, Bottom Left Coord.'
            );
        }

        return $columns;
    }

    private function parseRow(array $row, array $columnIndexes, int $rowNumber, array &$errors): ?array
    {
        $locationReference = trim((string) ($row[$columnIndexes['location_reference']] ?? ''));

        $topLeft = $this->parseCoordinatePair($row[$columnIndexes['top_left']] ?? null);
        $topRight = $this->parseCoordinatePair($row[$columnIndexes['top_right']] ?? null);
        $bottomRight = $this->parseCoordinatePair($row[$columnIndexes['bottom_right']] ?? null);
        $bottomLeft = $this->parseCoordinatePair($row[$columnIndexes['bottom_left']] ?? null);

        if ($locationReference === '' && !$topLeft && !$topRight && !$bottomRight && !$bottomLeft) {
            return null;
        }

        if ($locationReference === '') {
            $errors[] = "Row {$rowNumber}: Location Reference is required.";
            return null;
        }

        foreach ([
            'Top Left Coord' => $topLeft,
            'Top Right Coord' => $topRight,
            'Bottom Right Coord' => $bottomRight,
            'Bottom Left Coord' => $bottomLeft,
        ] as $label => $coordinate) {
            if ($coordinate === null) {
                $errors[] = "Row {$rowNumber}: Invalid {$label} value.";
                return null;
            }
        }

        return [
            'location_reference' => $locationReference,
            'top_left_lat' => $topLeft['lat'],
            'top_left_lng' => $topLeft['lng'],
            'top_right_lat' => $topRight['lat'],
            'top_right_lng' => $topRight['lng'],
            'bottom_right_lat' => $bottomRight['lat'],
            'bottom_right_lng' => $bottomRight['lng'],
            'bottom_left_lat' => $bottomLeft['lat'],
            'bottom_left_lng' => $bottomLeft['lng'],
        ];
    }

    private function parseCoordinatePair(mixed $value): ?array
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', (string) $value));

        if (count($parts) < 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return null;
        }

        return [
            'lat' => (float) $parts[0],
            'lng' => (float) $parts[1],
        ];
    }

    private function normalizeHeader(mixed $header): string
    {
        return Str::of((string) $header)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();
    }
}
