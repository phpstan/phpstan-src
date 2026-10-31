<?php declare(strict_types = 1);

namespace GenericTypeAliasWrongArgCount;

// ---------------------------------------------------------------------------
// Too many type args
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Single<T> array{value: T}
 */
final class TooManyArgs
{
	/**
	 * @param Single<int, string> $x
	 */
	public function badParam(array $x): void {}

	/**
	 * @return Single<int, string>
	 */
	public function badReturn(): array { return ['value' => 1]; }

	/**
	 * @param Single<int> $ok
	 */
	public function goodParam(array $ok): void {}

	/**
	 * @return Single<int>
	 */
	public function goodReturn(): array { return ['value' => 1]; }
}

// ---------------------------------------------------------------------------
// Too few required type args (partial application of multi-param alias)
// ---------------------------------------------------------------------------

/**
 * @phpstan-type KeyVal<TKey of array-key, TValue> array{key: TKey, value: TValue}
 */
final class TooFewArgs
{
	/**
	 * @param KeyVal<string> $x
	 */
	public function badParam(array $x): void {}

	/**
	 * @return KeyVal<string>
	 */
	public function badReturn(): array { return ['key' => 'k', 'value' => 'v']; }

	/**
	 * @param KeyVal<string, int> $ok
	 */
	public function goodParam(array $ok): void {}

	/**
	 * @return KeyVal<string, int>
	 */
	public function goodReturn(): array { return ['key' => 'k', 'value' => 1]; }
}



