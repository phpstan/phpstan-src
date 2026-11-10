<?php // lint >= 8.6

namespace PartialFunctionApplicationPositions;

final class Foo
{
	public function m(int $a, string $b): string { return $b; }
	public static function s(int $a, string $b): string { return $b; }
	public function __invoke(int $a, int $b): int { return $a + $b; }

	public function doFoo(): void
	{
		$a = $this->m(1, ?);
		$b = self::s(?, 'x');
		$c = static::s(1, ...);
	}
}

function f(int $a, string $b, int ...$rest): string { return $b; }

function test(Foo $foo, array $arr, ?Foo $maybe, string $s): void
{
	$f = f(1, ?);
	$g = f(?, 'a', ...);
	$h = str_replace(' ', '-', ?);
	$i = $foo->m(?, $s);
	$j = Foo::s(b: ?, a: 1);
	$k = $foo(1, ?);
	$l = (fn (int $x, int $y): int => $x + $y)(?, 2);
	$m = f(1, $undefinedVar, ?);
	$n = f($assigned = 5, ?);
	if (f(1, ?)) {}
	foreach ([f(1, ?)] as $x) {}
	for ($z = 0; count($arr) > $z; $z++) {}
	$o = array_map(strtoupper(?), ['a']);
	$p = array_filter([1, 2], is_int(?));
	$q = in_array(?, ['a', 'b'], true);
	$r = $arr[array_key_first(?)] ?? null;
	$t = 'abc' |> str_replace('a', 'b', ?);
	$u = 'abc' |> strlen(...);
	$v = $maybe?->m(1, ?);
	$w = new Foo(?);
	f(1, ?);
	define(?, ?);
	$y = compact(?);
	$aa = count(?) === 1;
	$bb = sizeof(?) > 0;
	$cc = $s === implode(',', ?);
	$dd = preg_match('/a/', ?);
	$ee = curl_setopt(?, CURLOPT_URL, 'x');
	$ff = array_walk(?, fn () => 1);
	$gg = array_find(?, fn () => true);
	$hh = \Closure::bind(?, null);
	$ii = (function () {})->bindTo(?);
	$jj = (function () {})(...);
	$kk = (function (int $x) {})(?);
	$ll = (fn (int $x) => $x)(?);
	$mm = $s(?);
	$nn = [$foo, 'm'](?, 'b');
	while (f(1, ?)) { break; }
	$oo = f(1, ?) instanceof \Closure;
	$pp = is_callable(f(1, ?));
	$qq = match (true) { default => f(1, ?) };
	$rr = isset($arr[f(1, ?)]);
	$ss = f(1, ?) ?? null;
	$tt = !f(1, ?);
	$uu = (string) f(1, ?);
	echo f(1, ...);
	yield_test();
}

function yield_test(): \Generator
{
	$x = f(yield 1, ?);
	return $x;
}

final class ConstExprs
{
	public const C = f(1, ?);
	public \Closure $p = strtoupper(?);
	public function __construct(public \Closure $c = str_replace('a', 'b', ?)) {}
}
