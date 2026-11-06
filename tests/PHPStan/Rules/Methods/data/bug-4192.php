<?php declare(strict_types = 1);

namespace Bug4192;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template T
 */
class Arrayy
{

	/** @var array<TKey, T> */
	private array $array;

	/**
	 * @param array<TKey, T> $array
	 */
	public function __construct(array $array)
	{
		$this->array = $array;
	}

	/**
	 * @param \Closure|null $closure
	 * @phpstan-param null|(\Closure(T,TKey): bool)|(\Closure(T): bool)|(\Closure(TKey): bool) $closure
	 */
	public function filter($closure = null, int $flag = \ARRAY_FILTER_USE_BOTH): void
	{
		if (!$closure) {
			return;
		}

		if ($flag === \ARRAY_FILTER_USE_KEY) {
			/** @phpstan-var \Closure(TKey): bool $closure */
			$closure = $closure;
			$generator = function () use ($closure): void {
				foreach ($this->array as $key => $value) {
					assertType('bool', $closure($key));
				}
			};
			$generator();
		} elseif ($flag === \ARRAY_FILTER_USE_BOTH) {
			/** @phpstan-var \Closure(T,TKey): bool $closure */
			$closure = $closure;
			$generator = function () use ($closure): void {
				foreach ($this->array as $key => $value) {
					assertType('bool', $closure($value, $key));
				}
			};
			$generator();
		} else {
			/** @phpstan-var \Closure(T): bool $closure */
			$closure = $closure;
			$generator = function () use ($closure): void {
				foreach ($this->array as $key => $value) {
					assertType('bool', $closure($value));
				}
			};
			$generator();
		}
	}

}

(new Arrayy([0 => 1, 1 => 2, 2 => 3, 3 => 4, 7 => 7]))->filter(
	static function ($value): bool {
		return $value % 2 !== 0;
	},
);

(new Arrayy([0 => 1, 1 => 2, 2 => 3, 3 => 4, 7 => 7]))->filter(
	static function ($key, $value): bool {
		return ($value % 2 !== 0) && (($key & 2) !== 0);
	},
	\ARRAY_FILTER_USE_BOTH,
);
