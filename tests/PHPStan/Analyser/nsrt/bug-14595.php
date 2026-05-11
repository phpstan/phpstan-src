<?php

declare(strict_types = 1);

namespace Bug14595;

use function PHPStan\Testing\assertType;

/**
 * @param array<mixed> $data
 * @param array{
 *     multiple: 0|1|2
 *   , total: bool
 *  } $options
 */
function formulaire_edition(array $data, array $options): void {
    $instructions = [ ];
    $instructions[] = "foo";
    if ($options['multiple'] != 1 || $options['total'])
        $instructions[] = "bar";
    assertType('0|1|2', $options['multiple']);
    if (!$options['total'])
        $instructions[] = "baz";
    assertType('0|1|2', $options['multiple']);
    if (!$options['total'])
        $instructions[] = "qux";
    assertType('0|1|2', $options['multiple']);
}
