<?php declare(strict_types=1);

namespace Bug15435Rule;

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
    sort($keys);
}
