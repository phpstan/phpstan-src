<?php declare(strict_types = 1);

namespace Bug15498;

use function PHPStan\Testing\assertType;

function derivedThroughAssignOps(): void
{
	foreach (['a' => 'X', 'b' => 'YY'] as $k => $v) {
		$s = 'p';
		$s .= $v;
		$n = 1;
		$n += strlen($v);
		$c = null;
		$c ??= $v;
		if ($k === 'a') {
			assertType("'pX'", $s);
			assertType('2', $n);
			assertType("'X'", $c);
		} else {
			assertType("'pYY'", $s);
			assertType('3', $n);
			assertType("'YY'", $c);
		}
	}
}

/** @param list<list<array<string, mixed>>> $pl */
function accumulators(array $pl): string
{
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
		assertType('non-falsy-string&uppercase-string', $out);
	}
	assertType('uppercase-string', $out);
	return $out;
}
