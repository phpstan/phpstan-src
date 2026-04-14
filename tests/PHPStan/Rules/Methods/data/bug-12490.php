<?php declare(strict_types = 1);

namespace Bug12490;

/**
 * @template TGet
 * @template TSet
 */
class Attribute
{
	/** @var (callable(mixed, array<string, mixed>): TGet)|null */
	public $get;

	/** @var (callable(TSet, array<string, mixed>): mixed)|null*/
	public $set;

	/**
	 * @param  (callable(mixed, array<string, mixed>): TGet)|null  $get
	 * @param  (callable(TSet, array<string, mixed>): mixed)|null  $set
	 */
	public function __construct(?callable $get = null, ?callable $set = null)
	{
		$this->get = $get;
		$this->set = $set;
	}

	/**
	 * @template T
	 * @param  callable(mixed, array<string, mixed>): T  $get
	 * @return Attribute<T, never>
	 */
	public static function get(callable $get): self
	{
		return new self($get);
	}
}


class Foo
{
	public ?int $id = null;
	public ?string $surveyable_type = null;

	/**
	 * @return Attribute<null|string, never>
	 */
	protected function surveyedLink(): Attribute
	{
		return Attribute::get(fn () => $this->surveyable_type);
	}

	/** @return Attribute<null|float, never> */
	protected function packageWeightCalculated(): Attribute
	{
		return Attribute::get(fn () => $this->id === null ? null : round(50 * .15, 2));
	}

	/** @return Attribute<?int, never> */
	protected function durationMs(): Attribute
	{
		return Attribute::get(fn () => $this->id);
	}
}

/**
 * @template T
 */
class Container
{
	/** @var T */
	public $value;

	/**
	 * @param T $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}
}

class Bar
{
	/** @return Container<string> */
	public function test(?string $val): Container
	{
		return new Container($val);
	}
}
