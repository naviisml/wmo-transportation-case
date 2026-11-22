<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class BookRideData
{
    public function __construct(
        public int $residentId,
        public string $origin,
        public string $destination,
        public CarbonImmutable $scheduledAt,
        public int $distanceMeters,
    ) {}

    /** @param array{resident_id: int|string, origin: string, destination: string, scheduled_at: string, distance_meters: int|string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            residentId: (int) $data['resident_id'],
            origin: trim($data['origin']),
            destination: trim($data['destination']),
            scheduledAt: CarbonImmutable::parse($data['scheduled_at'])->utc(),
            distanceMeters: (int) $data['distance_meters'],
        );
    }

    /** @return array{resident_id: int, origin: string, destination: string, scheduled_at: CarbonImmutable, distance_meters: int} */
    public function toArray(): array
    {
        return [
            'resident_id' => $this->residentId,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'scheduled_at' => $this->scheduledAt,
            'distance_meters' => $this->distanceMeters,
        ];
    }

    public function requestHash(): string
    {
        return hash('sha256', json_encode([
            'resident_id' => $this->residentId,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'scheduled_at' => $this->scheduledAt->toIso8601String(),
            'distance_meters' => $this->distanceMeters,
        ], JSON_THROW_ON_ERROR));
    }
}
