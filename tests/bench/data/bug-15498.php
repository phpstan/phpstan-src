<?php declare(strict_types = 1);

namespace Bug15498;

/** @param list<list<array<string, mixed>>> $pl */
function t(array $pl): string {
    $out = '';
    foreach ($pl as $rows) {
        $a = 0; $b = 0; $c = 0; $d = 0; $e = 0; $f = 0; $g = 0;
        foreach ($rows as $r) {
            $a = $a + (float) $r['a'];
            $b = $b + (float) $r['b'];
            $c = $c + (float) $r['c'];
            $d = $d + (float) $r['d'];
            $e = $e + (float) $r['e'];
            $f = $f + (float) $r['f'];
            $g = $g + (float) $r['g'];
        }
        $out .= "$a $b $c $d $e $f $g";
    }
    return $out;
}
