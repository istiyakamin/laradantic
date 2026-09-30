<?php

declare(strict_types=1);

use LaraDantic\Exceptions\SchemaValidationException;
use LaraDantic\Tests\Fixtures\CustomRulesBooking;
use LaraDantic\Tests\Fixtures\FlightBooking;

function validFlightBookingData(): array
{
    return [
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
}

it('passes validation for fully valid data', function () {
    $result = FlightBooking::validate(validFlightBookingData());

    expect($result->passes())->toBeTrue()
        ->and($result->fails())->toBeFalse()
        ->and($result->errors())->toBe([]);
});

it('fails validation when a required field is missing', function () {
    $data = validFlightBookingData();
    unset($data['origin']);

    $result = FlightBooking::validate($data);

    expect($result->fails())->toBeTrue()
        ->and($result->errors())->toHaveKey('origin');
});

it('fails validation when an enum property has an invalid value', function () {
    $data = validFlightBookingData();
    $data['cabin'] = 'unobtanium';

    $result = FlightBooking::validate($data);

    expect($result->fails())->toBeTrue()
        ->and($result->errors())->toHaveKey('cabin');
});

it('enforces the Min/Max attributes as min/max validation rules', function () {
    $data = validFlightBookingData();
    $data['passengers'] = 0;

    $result = FlightBooking::validate($data);

    expect($result->fails())->toBeTrue()
        ->and($result->errors())->toHaveKey('passengers');
});

it('validates nested schema properties inside a typed array using dot notation', function () {
    $data = validFlightBookingData();
    $data['passengers_list'] = [
        ['passportNumber' => 'X1234567'], // missing required "name"
    ];

    $result = FlightBooking::validate($data);

    expect($result->fails())->toBeTrue()
        ->and($result->errors())->toHaveKey('passengers_list.0.name');
});

it('validated() hydrates a schema instance when data is valid', function () {
    $booking = FlightBooking::validated(validFlightBookingData());

    expect($booking)->toBeInstanceOf(FlightBooking::class)
        ->and($booking->origin)->toBe('VIE');
});

it('validated() throws SchemaValidationException with structured errors when data is invalid', function () {
    $data = validFlightBookingData();
    unset($data['origin']);
    $data['cabin'] = 'unobtanium';

    try {
        FlightBooking::validated($data);
        $this->fail('Expected SchemaValidationException was not thrown.');
    } catch (SchemaValidationException $exception) {
        expect($exception->errors())->toHaveKeys(['origin', 'cabin']);
    }
});

it('allows a schema to fully override the inferred rules', function () {
    $failing = CustomRulesBooking::validate(['reference' => 'XYZ-123']);
    $passing = CustomRulesBooking::validate(['reference' => 'BK-123']);

    expect($failing->fails())->toBeTrue()
        ->and($failing->errors())->toHaveKey('reference')
        ->and($passing->passes())->toBeTrue();
});
