<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

/**
 * Describes which positional parameters of an array callback receive the
 * element value and the element key. `array_filter` varies this by its flag
 * argument (USE_ITEM / USE_KEY / USE_BOTH), while `array_all` / `array_any`
 * always pass value first and key second.
 */
final class ArrayCallbackParameterMapping
{

	private function __construct(
		private ?int $itemPosition,
		private ?int $keyPosition,
	)
	{
	}

	public static function item(): self
	{
		return new self(0, null);
	}

	public static function key(): self
	{
		return new self(null, 0);
	}

	public static function valueAndKey(): self
	{
		return new self(0, 1);
	}

	public function getItemPosition(): ?int
	{
		return $this->itemPosition;
	}

	public function getKeyPosition(): ?int
	{
		return $this->keyPosition;
	}

}
