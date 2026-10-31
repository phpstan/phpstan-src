<?php declare(strict_types = 1);

namespace GenericTypeAliasMissingTypehint;

// ---------------------------------------------------------------------------
// Raw usage of generic alias (no type args, no default) → should error
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Filter<TItem> array{items: list<TItem>}
 */
class RawUsage
{
	/**
	 * @param Filter<string> $a  OK: type arg provided
	 * @param Filter         $b  ERROR: Filter requires 1 type arg
	 */
	public function check(array $a, array $b): void {}

	/**
	 * @return Filter<int>  OK
	 */
	public function getFiltered(): array { return ['items' => []]; }

	/**
	 * @return Filter  ERROR: Filter requires 1 type arg
	 */
	public function getRaw(): array { return ['items' => []]; }
}

// ---------------------------------------------------------------------------
// Alias with a default — bare usage should NOT error
// ---------------------------------------------------------------------------

/**
 * @phpstan-type WithDefault<T = string> array{value: T}
 */
class DefaultedUsage
{
	/**
	 * @param WithDefault      $implicit  OK: T has a default
	 * @param WithDefault<int> $explicit  OK: T provided
	 */
	public function check(array $implicit, array $explicit): void {}
}

// ---------------------------------------------------------------------------
// Two-param alias, one required, one defaulted
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Pair<TFirst, TSecond = bool> array{first: TFirst, second: TSecond}
 */
class PartialDefault
{
	/**
	 * @param Pair<string>       $oneArg   OK: TFirst provided, TSecond defaults to bool
	 * @param Pair<string, int>  $twoArgs  OK: both provided
	 * @param Pair               $noArgs   ERROR: TFirst has no default
	 */
	public function check(array $oneArg, array $twoArgs, array $noArgs): void {}
}

// ---------------------------------------------------------------------------
// Imported generic alias — raw usage should also error
// ---------------------------------------------------------------------------

/**
 * @phpstan-import-type Filter from RawUsage
 */
class ImportedRawUsage
{
	/**
	 * @param Filter<bool> $ok    OK
	 * @param Filter       $bad   ERROR: Filter requires 1 type arg
	 */
	public function check(array $ok, array $bad): void {}
}

