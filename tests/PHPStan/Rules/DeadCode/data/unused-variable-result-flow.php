<?php // lint >= 8.1

namespace UnusedVariableResultFlow;

function capturesAsArguments(): array
{
	$arrow = 1;
	$closure = 2;
	return array_map(static fn ($x) => $x + $arrow, array_map(static function ($x) use ($closure) { return $x + $closure; }, [1]));
}

function arrowIsolation(): array
{
	$value = 1;
	$shadowed = 2;
	return [fn () => $value = 3, fn ($shadowed) => $shadowed, $value];
}

function arrowThrow(): int
{
	$value = 1;
	$callback = fn () => throw new \Exception();
	echo $callback::class;
	return $value;
}

function loopExit(): int
{
	$values = [1, 2];
	while ($values !== []) {
		array_pop($values);
	}
	$result = 3;
	return $result;
}

function forExpressions(): int
{
	$init = 1;
	$condition = 2;
	$update = 3;
	for ($i = $init; $condition, $i < 4; $i += $update) {
		echo $i;
	}
	return $i;
}

function switchConditions(int $subject): int
{
	$value = 1;
	switch ($subject) {
		case 0:
			return $value;
		case ($value = 2):
			return $value;
		default:
			return $value;
	}
}

function matchConditions(int $subject): int
{
	$value = 1;
	return match ($subject) {
		0 => $value,
		($value = 2), ($value = 3) => $value,
		default => $value,
	};
}

function branchOverwrite(bool $flag): int
{
	$value = 1;
	if ($flag) {
		$value = 2;
	} else {
		$value = 3;
	}
	return $value;
}

function finallyReturn(bool $flag): int
{
	$value = 1;
	try {
		if ($flag) {
			return $value;
		}
		$value = 2;
		throw new \Exception();
	} finally {
		echo $value;
	}
}

function finallyOverride(): int
{
	$value = 1;
	try {
		return 0;
	} finally {
		$value = 2;
		return $value;
	}
}

function nestedContinue(): int
{
	$value = 0;
	for ($i = 0; $i < 2; $i++) {
		try {
			while (rand(0, 1)) {
				$value = 1;
				continue 2;
			}
		} finally {
			echo $value;
		}
	}
	return $value;
}

function catchReads(callable $callback): void
{
	$value = 1;
	try {
		$callback();
		$value = 2;
	} catch (\RuntimeException $e) {
		echo $value, $e->getMessage();
	}
}

function namespacedCompact(): int
{
	$value = 1;
	compact('value');
	return 0;
}

function compact(string $value): void
{
	echo $value;
}

function overwrittenInNonEmptyLoop(): int
{
	$value = 1;
	foreach ([2, 3] as $item) {
		$value = $item;
	}
	return $value;
}

function doOnce(): int
{
	$value = 1;
	do {
		echo $value;
		$value = 2;
	} while (false);
	return 0;
}

function neverForLoop(): void
{
	$value = 1;
	for (; false;) {
		echo $value;
	}
}

function referenceArgument(callable $reader): void
{
	$value = 1;
	readReference($value);
	$value = 2;
	$reader();
}

function readReference(int &$value): void
{
	echo $value;
}

function arrayReference(): void
{
	$value = 1;
	$array = [[&$value]];
	$array[0][0] = 2;
	echo $value;
}

function firstClassCallables(): array
{
	$object = new \ArrayObject();
	$method = 'getIterator';
	$function = 'strlen';
	$class = \DateTimeImmutable::class;
	return [$object->$method(...), $function(...), $class::createFromFormat(...)];
}
