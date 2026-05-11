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

/**
 * @param array<mixed> $data
 * @param array{
 *     multiple: 0|1|2
 *  } $options
 */
function formulaire_edition_separate_bool(array $data, array $options, bool $total): void {
    $instructions = [ ];
    $instructions[] = "foo";
    if ($options['multiple'] != 1 || $total) {
        $instructions[] = "bar";
	}
    assertType('0|1|2', $options['multiple']);
    if (!$total) {
        $instructions[] = "baz";
	}
    assertType('0|1|2', $options['multiple']);
    if (!$total) {
        $instructions[] = "qux";
	}
    assertType('0|1|2', $options['multiple']);
}

/**
 * @param array<mixed> $data
 * @param array{
 *     multiple: 0|1|2
 *  } $options
 */
function multiple_guard_constant_arrays(array $data, array $options, bool $flag1, bool $flag2): void {
    $instructions = [];
    if ($flag1) {
        $instructions[] = "a";
        $instructions[] = "b";
    } else {
        $instructions[] = "c";
    }
    // $instructions is array{'a', 'b'}|array{'c'} — guard has 2 constant arrays with key counts 2 and 1
    if ($options['multiple'] != 1 || $flag2) {
        $instructions[] = "d";
    }
    assertType('0|1|2', $options['multiple']);
    if (!$flag2) {
        $instructions[] = "e";
    }
    assertType('0|1|2', $options['multiple']);
    if (!$flag2) {
        $instructions[] = "f";
    }
    assertType('0|1|2', $options['multiple']);
}
