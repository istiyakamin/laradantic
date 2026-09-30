<?php

declare(strict_types=1);

use LaraDantic\Exceptions\ToolException;
use LaraDantic\Tests\Fixtures\Tools\CreateFlightBookingTool;
use LaraDantic\Tests\Fixtures\Tools\SearchFlightTool;
use LaraDantic\Tools\ToolRegistry;

it('registers tools by class-string and by instance', function () {
    $registry = new ToolRegistry;
    $registry->register(CreateFlightBookingTool::class);
    $registry->register(new SearchFlightTool);

    expect($registry->has('create_flight_booking'))->toBeTrue()
        ->and($registry->has('search_flight'))->toBeTrue()
        ->and($registry->all())->toHaveCount(2);
});

it('throws when getting an unregistered tool', function () {
    (new ToolRegistry)->get('does_not_exist');
})->throws(ToolException::class);

it('throws when registering a class that is not a Tool', function () {
    (new ToolRegistry)->register(stdClass::class);
})->throws(ToolException::class);

it('generates OpenAI-compatible tool definitions from the schema of each registered tool', function () {
    $registry = new ToolRegistry;
    $registry->register(CreateFlightBookingTool::class);

    $definitions = $registry->definitions();

    expect($definitions)->toHaveCount(1)
        ->and($definitions[0]['type'])->toBe('function')
        ->and($definitions[0]['function']['name'])->toBe('create_flight_booking')
        ->and($definitions[0]['function']['description'])->toBe('Create a flight booking request.')
        ->and($definitions[0]['function']['parameters']['type'])->toBe('object')
        ->and($definitions[0]['function']['parameters']['properties'])->toHaveKey('origin');
});
