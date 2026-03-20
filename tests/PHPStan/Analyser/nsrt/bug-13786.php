<?php declare(strict_types=1);

namespace Bug13786;

use function PHPStan\Testing\assertType;

/** @var array<mixed> $arr */

/** @var non-empty-list<'a'|'b'|'c'> $cols */

$total = [ ];
foreach ($arr as $id => $dummy) {
    $total[$id] = [ ];
    foreach ($cols as $col) {
        $total[$id][$col] = '0';
    }
    assertType("non-empty-array<'a'|'b'|'c'|'d', '0'>", $total[$id]);
    $total[$id]['d'] = '0';
    assertType("non-empty-array<'a'|'b'|'c'|'d', '0'>&hasOffsetValue('d', '0')", $total[$id]);
}
