<?php

namespace BugYieldOversizedSelfRejectionNsrt;

use function PHPStan\Testing\assertType;

function build(string $eventClass): array
{
    $element = 'Deleted' === $eventClass ? 'old' : 'new';

    if (rand()) {
        $r = [
            'eventClass' => $eventClass,
            'changes' => [[$element => [1, '2022-08-04', [['08:00', '12:00']]]]],
            'matchedTimechecks' => [],
            'invalidates' => [[1, '2022-08-04']],
        ];
    } elseif (rand()) {
        $r = [
            'eventClass' => $eventClass,
            'changes' => [[$element => [1, '2022-08-04', [['08:00', '12:00'], ['14:00', '18:00']]]]],
            'matchedTimechecks' => [],
            'invalidates' => [[1, '2022-08-04']],
        ];
    } elseif (rand()) {
        $r = [
            'eventClass' => $eventClass,
            'changes' => [[$element => [1, '2022-08-04', [['00:00', '00:00']]]]],
            'matchedTimechecks' => [],
            'invalidates' => [[1, '2022-08-04']],
        ];
    } elseif (rand()) {
        $r = [
            'eventClass' => $eventClass,
            'changes' => [[$element => [1, '2022-08-04', [['22:00', '04:00']]]]],
            'matchedTimechecks' => [],
            'invalidates' => [[1, '2022-08-04/2022-08-05']],
        ];
    } elseif (rand()) {
        $r = [
            'eventClass' => $eventClass,
            'changes' => [[$element => [1, '2022-08-04', [['16:00', '23:00']], 'matchedTimecheckIds' => [42]]]],
            'matchedTimechecks' => [42 => [1, '2022-08-05T00:05/2022-08-05T02:00']],
            'invalidates' => [[1, '2022-08-04/2022-08-05']],
        ];
    } elseif (rand()) {
        $r = [
            'eventClass' => $eventClass,
            'changes' => [
                [$element => [1, '2022-08-04', [['08:00', '12:00']]]],
                [$element => [1, '2022-08-10', [['08:00', '12:00']]]],
            ],
            'matchedTimechecks' => [],
            'invalidates' => [[1, '2022-08-04'], [1, '2022-08-10']],
        ];
    } elseif (rand()) {
        $r = [
            'eventClass' => $eventClass,
            'changes' => [
                [$element => [1, '2022-08-04', [['08:00', '12:00']]]],
                [$element => [1, '2022-08-05', [['08:00', '12:00']]]],
                [$element => [1, '2022-08-06', [['08:00', '12:00']]]],
            ],
            'matchedTimechecks' => [],
            'invalidates' => [[1, '2022-08-04/2022-08-06']],
        ];
    } elseif (rand()) {
        $r = [
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
    } else {
        $r = [
            'eventClass' => $eventClass,
            'changes' => [
                [$element => [1, '2022-08-04', [['08:00', '12:00']]]],
                [$element => [1, '2022-08-05', [['08:00', '12:00']]]],
            ],
            'matchedTimechecks' => [],
            'invalidates' => [],
            'persistenceEnabled' => false,
        ];
    }

    assertType("non-empty-array<literal-string&non-falsy-string, array{}|(array{42: array{1, '2022-08-05T00:05/2022-08-05T02:00'}}&oversized-array)|bool|(list{0: array{1, '2022-08-04'}, 1?: array{1, '2022-08-10'}}&oversized-array)|(list{0: non-empty-array{old?: array{1, '2022-08-04', array{array{'08:00', '12:00'}}}, new?: array{1, '2022-08-04', array{array{'08:00', '12:00'}}}}, 1?: non-empty-array{old?: array{1, '2022-08-05', array{array{'08:00', '12:00'}}}, new?: array{1, '2022-08-05', array{array{'08:00', '12:00'}}}}|non-empty-array{old?: array{1, '2022-08-10', array{array{'08:00', '12:00'}}}, new?: array{1, '2022-08-10', array{array{'08:00', '12:00'}}}}, 2?: non-empty-array{old?: array{1, '2022-08-06', array{array{'08:00', '12:00'}}}, new?: array{1, '2022-08-06', array{array{'08:00', '12:00'}}}}}&oversized-array)|(list{array{1, '2022-08-04'}, array{2, '2022-08-05/2022-08-06'}, array{3, '2022-08-06'}, array{3, '2022-08-10'}}&oversized-array)|(list{array{1, '2022-08-04/2022-08-05'|'2022-08-04/2022-08-06'}}&oversized-array)|(list{non-empty-array{old?: array{0: 1, 1: '2022-08-04', 2: array{array{'16:00', '23:00'}}, matchedTimecheckIds: array{42}}, new?: array{0: 1, 1: '2022-08-04', 2: array{array{'16:00', '23:00'}}, matchedTimecheckIds: array{42}}}}&oversized-array)|(list{non-empty-array{old?: array{1, '2022-08-04', array{array{'00:00', '00:00'}}}, new?: array{1, '2022-08-04', array{array{'00:00', '00:00'}}}}}&oversized-array)|(list{non-empty-array{old?: array{1, '2022-08-04', array{array{'08:00', '12:00'}, array{'14:00', '18:00'}}}, new?: array{1, '2022-08-04', array{array{'08:00', '12:00'}, array{'14:00', '18:00'}}}}}&oversized-array)|(list{non-empty-array{old?: array{1, '2022-08-04', array{array{'08:00', '12:00'}}}, new?: array{1, '2022-08-04', array{array{'08:00', '12:00'}}}}, non-empty-array{old?: array{2, '2022-08-05', array{array{'08:00', '12:00'}}}, new?: array{2, '2022-08-05', array{array{'08:00', '12:00'}}}}, non-empty-array{old?: array{2, '2022-08-06', array{array{'08:00', '12:00'}}}, new?: array{2, '2022-08-06', array{array{'08:00', '12:00'}}}}, non-empty-array{old?: array{3, '2022-08-06', array{array{'08:00', '12:00'}}}, new?: array{3, '2022-08-06', array{array{'08:00', '12:00'}}}}, non-empty-array{old?: array{3, '2022-08-10', array{array{'08:00', '12:00'}}}, new?: array{3, '2022-08-10', array{array{'08:00', '12:00'}}}}}&oversized-array)|(list{non-empty-array{old?: array{1, '2022-08-04', array{array{'22:00', '04:00'}}}, new?: array{1, '2022-08-04', array{array{'22:00', '04:00'}}}}}&oversized-array)|string>&oversized-array", $r);

    return $r;
}
