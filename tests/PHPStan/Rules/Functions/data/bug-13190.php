<?php // lint >= 8.0
declare(strict_types = 1);

namespace Bug13190;

/**
 * @template T
 */
interface Box
{
	/**
	 * @return T
	 */
	public function toInner(): mixed;
}

/**
 * @template T
 *
 * @implements Box<T>
 */
final class BoxImpl implements Box
{
	/**
	 * @param T $value
	 */
	public function __construct(
		private mixed $value,
	) {}

	/**
	 * @return T
	 */
	#[\Override]
	public function toInner(): mixed
	{
		return $this->value;
	}
}

/**
 * @template T
 *
 * @param T|Box<T> $to_box
 *
 * @return Box<T>
 */
function inbox($to_box): Box
{
	if ($to_box instanceof Box) {
		return $to_box;
	} else {
		return new BoxImpl($to_box);
	}
}

/**
 * @implements Box<int>
 */
final class IntBox implements Box
{
	#[\Override]
	public function toInner(): int
	{
		return 0;
	}
}

/**
 * @template T
 *
 * @param T|Box<T> $to_box
 *
 * @return Box<T>
 */
function inbox_concrete_impl($to_box): Box
{
	if ($to_box instanceof IntBox) {
		return $to_box;
	} else {
		return new BoxImpl($to_box);
	}
}
