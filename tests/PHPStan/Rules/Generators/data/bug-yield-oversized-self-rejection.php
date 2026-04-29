<?php declare(strict_types=1);

namespace BugYieldOversizedSelfRejection;

use Generator;

/**
 * @param callable(string): iterable<string, mixed> $fn
 * @return Generator<string, mixed>
 */
function scollect(callable $fn): Generator
{
    foreach (['Created', 'Deleted'] as $eventClass) {
        yield from $fn($eventClass);
    }
}

scollect(static function (string $eventClass): iterable {
    $element = 'Deleted' === $eventClass ? 'old' : 'new';

    yield '1' => [
        'eventClass' => $eventClass,
        'changes' => [[$element => [1, '2022-08-04', [['08:00', '12:00']]]]],
        'matchedTimechecks' => [],
        'invalidates' => [[1, '2022-08-04']],
    ];
    yield '2' => [
        'eventClass' => $eventClass,
        'changes' => [[$element => [1, '2022-08-04', [['08:00', '12:00'], ['14:00', '18:00']]]]],
        'matchedTimechecks' => [],
        'invalidates' => [[1, '2022-08-04']],
    ];
    yield '3' => [
        'eventClass' => $eventClass,
        'changes' => [[$element => [1, '2022-08-04', [['00:00', '00:00']]]]],
        'matchedTimechecks' => [],
        'invalidates' => [[1, '2022-08-04']],
    ];
    yield '4' => [
        'eventClass' => $eventClass,
        'changes' => [[$element => [1, '2022-08-04', [['22:00', '04:00']]]]],
        'matchedTimechecks' => [],
        'invalidates' => [[1, '2022-08-04/2022-08-05']],
    ];
    yield '5' => [
        'eventClass' => $eventClass,
        'changes' => [[$element => [1, '2022-08-04', [['16:00', '23:00']], 'matchedTimecheckIds' => [42]]]],
        'matchedTimechecks' => [42 => [1, '2022-08-05T00:05/2022-08-05T02:00']],
        'invalidates' => [[1, '2022-08-04/2022-08-05']],
    ];
    yield '6' => [
        'eventClass' => $eventClass,
        'changes' => [
            [$element => [1, '2022-08-04', [['08:00', '12:00']]]],
            [$element => [1, '2022-08-10', [['08:00', '12:00']]]],
        ],
        'matchedTimechecks' => [],
        'invalidates' => [[1, '2022-08-04'], [1, '2022-08-10']],
    ];
    yield '7' => [
        'eventClass' => $eventClass,
        'changes' => [
            [$element => [1, '2022-08-04', [['08:00', '12:00']]]],
            [$element => [1, '2022-08-05', [['08:00', '12:00']]]],
            [$element => [1, '2022-08-06', [['08:00', '12:00']]]],
        ],
        'matchedTimechecks' => [],
        'invalidates' => [[1, '2022-08-04/2022-08-06']],
    ];
    yield '8' => [
        'eventClass' => $eventClass,
        'changes' => [
            [$element => [1, '2022-08-04', [['08:00', '12:00']]]],
            [$element => [2, '2022-08-05', [['08:00', '12:00']]]],
            [$element => [2, '2022-08-06', [['08:00', '12:00']]]],
            [$element => [3, '2022-08-06', [['08:00', '12:00']]]],
            [$element => [3, '2022-08-10', [['08:00', '12:00']]]],
        ],
        'matchedTimechecks' => [],
        'invalidates' => [[1, '2022-08-04'], [2, '2022-08-05/2022-08-06'], [3, '2022-08-06'], [3, '2022-08-10']],
    ];
    yield '9' => [
        'eventClass' => $eventClass,
        'changes' => [
            [$element => [1, '2022-08-04', [['08:00', '12:00']]]],
            [$element => [1, '2022-08-05', [['08:00', '12:00']]]],
        ],
        'matchedTimechecks' => [],
        'invalidates' => [],
        'persistenceEnabled' => false,
    ];
});
