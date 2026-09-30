<?php

declare(strict_types=1);

use LaraDantic\Exceptions\UnsupportedFeatureException;
use LaraDantic\Schema\Schema;
use LaraDantic\Tests\Fixtures\FlightBooking;
use LaraDantic\Tests\Fixtures\Passenger;

it('generates an object schema with properties and required fields', function () {
    $schema = FlightBooking::jsonSchema();

    expect($schema['type'])->toBe('object')
        ->and($schema['properties'])->toHaveKeys([
            'origin', 'destination', 'departureDate', 'returnDate', 'passengers', 'cabin', 'passengers_list',
        ])
        ->and($schema['required'])->toBe(['origin', 'destination', 'departureDate', 'cabin']);
});

it('marks nullable properties as nullable and excludes them from required', function () {
    $schema = FlightBooking::jsonSchema();

    expect($schema['properties']['returnDate'])->toBe([
        'type' => 'string',
        'nullable' => true,
    ]);
});

it('applies Description, Example, Min and Max attributes', function () {
    $schema = FlightBooking::jsonSchema();

    expect($schema['properties']['origin'])->toBe([
        'type' => 'string',
        'description' => 'Departure airport IATA code',
        'examples' => ['VIE'],
    ])->and($schema['properties']['passengers'])->toBe([
        'type' => 'integer',
        'minimum' => 1,
        'maximum' => 20,
        'default' => 1,
    ]);
});

it('generates a string enum schema from a backed string enum', function () {
    $schema = FlightBooking::jsonSchema();

    expect($schema['properties']['cabin'])->toBe([
        'type' => 'string',
        'enum' => ['economy', 'premium_economy', 'business', 'first'],
    ]);
});

it('recursively generates nested object schemas for typed arrays of schemas', function () {
    $schema = FlightBooking::jsonSchema();

    expect($schema['properties']['passengers_list'])->toBe([
        'type' => 'array',
        'items' => Passenger::jsonSchema(),
        'default' => [],
    ]);

    expect(Passenger::jsonSchema())->toBe([
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'passportNumber' => ['type' => 'string'],
        ],
        'required' => ['name', 'passportNumber'],
    ]);
});

it('produces valid, decodable JSON via jsonSchemaJson', function () {
    $json = FlightBooking::jsonSchemaJson();

    expect(json_decode($json, true))->toBe(FlightBooking::jsonSchema());
});

it('throws when a property type cannot be represented in JSON Schema', function () {
    $class = new class extends Schema
    {
        public stdClass $anything;
    };

    $class::jsonSchema();
})->throws(UnsupportedFeatureException::class);
