<?php declare(strict_types=1);

namespace Bug15435;

use function PHPStan\Testing\assertType;

/**
 * @param list<array{bucket: string, key: string}> $facts
 * @param list<string> $months
 */
function nestedLoop(array $facts, array $months): void
{
    $keys = [];
    foreach ($facts as $fact) {
        $n = 0;
        foreach ($months as $m) {
            if ('' !== $m) {
                ++$n;
            }
        }
        if ($n > 1) {
            $keys[] = $fact['key'];
        }
    }
    assertType('list<string>', $keys);
    sort($keys);
}

/**
 * @param list<array{bucket: string, key: string}> $facts
 * @param list<string> $months
 */
function flagInInner(array $facts, array $months): void
{
    $keys = [];
    foreach ($facts as $fact) {
        $n = 0;
        $found = false;
        foreach ($months as $m) {
            $n++;
            if ($n > 1) {
                $found = true;
            }
        }
        if ($found) {
            $keys[] = $fact['key'];
        }
    }
    assertType('list<string>', $keys);
}

/**
 * @param list<array{bucket: string, key: string}> $facts
 * @param list<string> $months
 */
function whileInner(array $facts, array $months): void
{
    $keys = [];
    foreach ($facts as $fact) {
        $n = 0;
        while ($n < count($months)) {
            $n++;
        }
        if ($n > 1) {
            $keys[] = $fact['key'];
        }
    }
    assertType('list<string>', $keys);
}

/**
 * @param list<array{bucket: string, key: string}> $facts
 * @param list<string> $months
 */
function forInner(array $facts, array $months): void
{
    $keys = [];
    foreach ($facts as $fact) {
        $n = 0;
        for ($i = 0; $i < count($months); $i++) {
            $n++;
        }
        if ($n > 1) {
            $keys[] = $fact['key'];
        }
    }
    assertType('list<string>', $keys);
}

/**
 * @param list<array{bucket: string, key: string}> $facts
 * @param list<string> $months
 */
function doWhileInner(array $facts, array $months): void
{
    $keys = [];
    foreach ($facts as $fact) {
        $n = 0;
        do {
            $n++;
        } while ($n < count($months));
        if ($n > 1) {
            $keys[] = $fact['key'];
        }
    }
    assertType('list<string>', $keys);
}

/**
 * @param list<array{bucket: string, key: string}> $facts
 * @param list<string> $months
 */
function whileOuter(array $facts, array $months): void
{
    $keys = [];
    $j = 0;
    while ($j < count($facts)) {
        $n = 0;
        foreach ($months as $m) {
            if ('' !== $m) {
                ++$n;
            }
        }
        if ($n > 1) {
            $keys[] = $facts[$j]['key'];
        }
        $j++;
    }
    assertType('list<string>', $keys);
}

/**
 * @param list<array{bucket: string, key: string}> $facts
 * @param list<string> $months
 */
function forOuter(array $facts, array $months): void
{
    $keys = [];
    for ($j = 0; $j < count($facts); $j++) {
        $n = 0;
        foreach ($months as $m) {
            if ('' !== $m) {
                ++$n;
            }
        }
        if ($n > 1) {
            $keys[] = $facts[$j]['key'];
        }
    }
    assertType('list<string>', $keys);
}

/**
 * @param list<array{bucket: string, key: string}> $facts
 * @param list<string> $months
 */
function doWhileOuter(array $facts, array $months): void
{
    $keys = [];
    $j = 0;
    do {
        $n = 0;
        foreach ($months as $m) {
            if ('' !== $m) {
                ++$n;
            }
        }
        if ($n > 1) {
            $keys[] = 'x';
        }
        $j++;
    } while ($j < count($facts));
    assertType("list<'x'>", $keys);
}

/**
 * @param list<list<string>> $groups
 * @param list<string> $months
 */
function threeLevels(array $groups, array $months): void
{
    $keys = [];
    foreach ($groups as $group) {
        foreach ($group as $item) {
            $n = 0;
            foreach ($months as $m) {
                ++$n;
            }
            if ($n > 1) {
                $keys[] = $item;
            }
        }
    }
    assertType('list<string>', $keys);
}
