<?php

declare(strict_types=1);

use LaraDantic\Exceptions\SchemaException;
use LaraDantic\Tests\Fixtures\Cabin;
use LaraDantic\Tests\Fixtures\FlightBooking;
use LaraDantic\Tests\Fixtures\Passenger;

it('hydrates primitive, nullable and enum properties from array data', function () {
    $booking = FlightBooking::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
        'departureDate' => '2026-10-15',
        'returnDate' => null,
        'passengers' => 2,
        'cabin' => 'business',
    ]);

    expect($booking->origin)->toBe('VIE')
        ->and($booking->destination)->toBe('DAC')
        ->and($booking->returnDate)->toBeNull()
        ->and($booking->passengers)->toBe(2)
        ->and($booking->cabin)->toBe(Cabin::Business);
});

it('casts loosely typed scalar input to the declared native type', function () {
    $booking = FlightBooking::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
        'departureDate' => '2026-10-15',
        'returnDate' => null,
        'passengers' => '3',
        'cabin' => Cabin::Economy,
    ]);

    expect($booking->passengers)->toBe(3);
});

it('applies declared defaults when a property is omitted', function () {
    $booking = FlightBooking::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
        'departureDate' => '2026-10-15',
        'returnDate' => null,
        'cabin' => 'economy',
    ]);

    expect($booking->passengers)->toBe(1)
        ->and($booking->passengers_list)->toBe([]);
});

it('treats a nullable property without input as null', function () {
    $booking = FlightBooking::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
        'departureDate' => '2026-10-15',
        'cabin' => 'economy',
    ]);

    expect($booking->returnDate)->toBeNull();
});

it('hydrates nested schemas inside a typed array', function () {
    $booking = FlightBooking::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
        'departureDate' => '2026-10-15',
        'returnDate' => null,
        'passengers' => 2,
        'cabin' => 'economy',
        'passengers_list' => [
            ['name' => 'Jane Doe', 'passportNumber' => 'X1234567'],
            ['name' => 'John Doe', 'passportNumber' => 'Y7654321'],
        ],
    ]);

    expect($booking->passengers_list)->toHaveCount(2)
        ->and($booking->passengers_list[0])->toBeInstanceOf(Passenger::class)
        ->and($booking->passengers_list[0]->name)->toBe('Jane Doe');
});

it('throws when a required property is missing', function () {
    FlightBooking::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
        'returnDate' => null,
        'cabin' => 'economy',
    ]);
})->throws(SchemaException::class, 'Missing required property [departureDate]');

it('throws when a value cannot be cast to the declared type', function () {
    FlightBooking::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
        'departureDate' => '2026-10-15',
        'returnDate' => null,
        'passengers' => 'not-a-number',
        'cabin' => 'economy',
    ]);
})->throws(SchemaException::class);

it('throws when an invalid enum value is given', function () {
    FlightBooking::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
        'departureDate' => '2026-10-15',
        'returnDate' => null,
        'cabin' => 'unobtanium',
    ]);
})->throws(SchemaException::class);

it('serializes a schema back into a plain array, round-tripping enums and nested schemas', function () {
    $data = [
        'origin' => 'VIE',
        'destination' => 'DAC',
        'departureDate' => '2026-10-15',
        'returnDate' => null,
        'passengers' => 2,
        'cabin' => 'business',
        'passengers_list' => [
            ['name' => 'Jane Doe', 'passportNumber' => 'X1234567'],
        ],
    ];

    $booking = FlightBooking::from($data);

    expect($booking->toArray())->toBe($data);
});
