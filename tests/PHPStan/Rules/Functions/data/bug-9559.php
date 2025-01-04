<?php declare(strict_types = 1);

namespace Bug9559;

class ZZ {};
function foo(?ZZ $z = null, ?int $a = 0, ?string $b = "x"): string { return "bah"; }

function doit(int $x): void {
	$call = [];
	if ($x) $call['a'] = 45;
	foo(...array_merge($call, [ "b" => "3" ]));
}
