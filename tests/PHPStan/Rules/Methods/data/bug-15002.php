<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15002;

class Acme {
	public function foo(): int {
		return 1;
	}

	public ?Acme $prop = null;

	public static ?Acme $stat = null;

	public static function create(): ?Acme {
		return new Acme();
	}
}

function getAcme(bool $enabled = true): ?Acme {
	return $enabled ? new Acme() : null;
}

$bar = getAcme()?->foo();
$baz = getAcme()?->foo();

function chained(): void
{
	$a = getAcme()?->prop?->prop?->foo();
	$b = getAcme()?->prop?->prop?->foo();
}

function staticProperty(): void
{
	$a = Acme::$stat?->foo();
	$b = Acme::$stat?->foo();
}

function staticMethodCall(): void
{
	$a = Acme::create()?->foo();
	$b = Acme::create()?->foo();
}

function staticMethodCallChained(): void
{
	$a = Acme::create()?->prop?->foo();
	$b = Acme::create()?->prop?->foo();
}

function staticPropertyChained(): void
{
	$a = Acme::$stat?->prop?->foo();
	$b = Acme::$stat?->prop?->foo();
}

function maybeCertaintyFromMerge(bool $c): void
{
	if ($c) {
		if (getAcme() !== null) {
			echo 1;
		}
	}

	$b = getAcme()?->foo();
}

function insideCoalesce(): void
{
	$a = getAcme()?->foo() ?? 0;
	$b = getAcme()?->foo() ?? 0;
}

function insideForLoop(): void
{
	for ($i = 0; $i < 3; $i++) {
		$a = getAcme()?->foo();
	}
}

function insideWhileLoop(): void
{
	while (rand(0, 1) === 1) {
		$a = getAcme()?->foo();
		$b = getAcme()?->foo();
	}
}

function afterTernary(bool $c): void
{
	$x = $c ? getAcme()?->foo() : null;
	$y = getAcme()?->foo();
}

function afterTryCatch(): void
{
	try {
		$a = getAcme()?->foo();
	} catch (\Throwable $e) {
	}

	$b = getAcme()?->foo();
}

function insideForeach(): void
{
	foreach ([1, 2] as $i) {
		$a = getAcme()?->foo();
	}
}

function insideDoWhile(): void
{
	do {
		$a = getAcme()?->foo();
	} while (rand(0, 1) === 1);

	$b = getAcme()?->foo();
}

function afterSwitch(int $i): void
{
	switch ($i) {
		case 1:
			$a = getAcme()?->foo();
			break;
	}

	$b = getAcme()?->foo();
}

function afterMatch(int $i): void
{
	$x = match ($i) {
		1 => getAcme()?->foo(),
		default => null,
	};
	$y = getAcme()?->foo();
}

function afterClosure(): void
{
	$f = function (): void {
		$a = getAcme()?->foo();
		$b = getAcme()?->foo();
	};
	$g = getAcme()?->foo();
}

function twiceInArguments(): void
{
	var_dump(getAcme()?->foo(), getAcme()?->foo());
}
