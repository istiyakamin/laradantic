<?php

declare(strict_types=1);

use LaraDantic\Tests\Fixtures\Cabin;
use LaraDantic\Tests\Fixtures\Passenger;
use LaraDantic\Tools\ToolResult;

it('builds a successful result and normalizes schema/enum data for serialization', function () {
    $passenger = Passenger::from(['name' => 'Jane Doe', 'passportNumber' => 'X1234567']);

    $result = ToolResult::success(['passenger' => $passenger, 'cabin' => Cabin::Business]);

    expect($result->successful())->toBeTrue()
        ->and($result->failed())->toBeFalse()
        ->and($result->toArray())->toBe([
            'success' => true,
            'data' => [
                'passenger' => ['name' => 'Jane Doe', 'passportNumber' => 'X1234567'],
                'cabin' => 'business',
            ],
            'error' => null,
        ]);
});

it('builds a failure result from a plain message', function () {
    $result = ToolResult::failure('Something went wrong.');

    expect($result->failed())->toBeTrue()
        ->and($result->errorMessage())->toBe('Something went wrong.')
        ->and($result->data())->toBeNull();
});

it('builds an error result from an exception', function () {
    $result = ToolResult::error(new RuntimeException('boom'));

    expect($result->failed())->toBeTrue()
        ->and($result->errorMessage())->toBe('boom');
});
