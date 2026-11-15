<?php

namespace RememberPossiblyImpureFunctionValuesProperties;

use function PHPStan\Testing\assertType;

class StateMachine
{

	/** @var 'ready'|'done' */
	public $state = 'ready';

	/** @var 'ready'|'done' */
	public static $staticState = 'ready';

	public function advance(): bool
	{
		$this->state = 'done';
		return true;
	}

	/** @phpstan-pure */
	public function pure(): bool
	{
		return true;
	}

	/** @phpstan-impure */
	public function impure(): bool
	{
		$this->state = 'done';
		return true;
	}

	public static function advanceStatic(): bool
	{
		self::$staticState = 'done';
		return true;
	}

	/** @phpstan-pure */
	public static function pureStatic(): bool
	{
		return true;
	}

	public function doFoo(): void
	{
		$this->state = 'ready';
		$this->advance();
		assertType("'ready'", $this->state);

		$this->state = 'ready';
		if ($this->advance()) {
			assertType("'ready'", $this->state);
		}

		$this->state = 'ready';
		$this->pure();
		assertType("'ready'", $this->state);

		$this->state = 'ready';
		$this->impure();
		assertType("'done'|'ready'", $this->state);
	}

	public function doLocalVariable(): void
	{
		$state = 'ready';
		$this->advance();
		assertType("'ready'", $state);
	}

	public function doOtherObject(self $other): void
	{
		$this->state = 'ready';
		$other->state = 'ready';
		$other->advance();
		assertType("'ready'", $other->state);
		assertType("'ready'", $this->state);
	}

	public function doNullsafe(?self $other): void
	{
		if ($other === null) {
			return;
		}

		$other->state = 'ready';
		$other?->advance();
		assertType("'ready'", $other->state);
	}

	public function doStatic(): void
	{
		self::$staticState = 'ready';
		self::advanceStatic();
		assertType("'ready'", self::$staticState);

		self::$staticState = 'ready';
		static::advanceStatic();
		assertType("'ready'", self::$staticState);

		self::$staticState = 'ready';
		self::pureStatic();
		assertType("'ready'", self::$staticState);
	}

	public function doStaticKeepsInstanceProperties(): void
	{
		$this->state = 'ready';
		self::advanceStatic();
		assertType("'ready'", $this->state);
	}

	public static function doStaticFromStaticMethod(): void
	{
		self::$staticState = 'ready';
		self::advanceStatic();
		assertType("'ready'", self::$staticState);

		self::$staticState = 'ready';
		self::pureStatic();
		assertType("'ready'", self::$staticState);
	}

}

class Holder
{

	public ?\stdClass $foo = null;

	/** @phpstan-assert-if-true !null $this->foo */
	public function hasFoo(): bool
	{
		return $this->foo !== null;
	}

	public function doAssertIfTrue(): void
	{
		if ($this->hasFoo()) {
			assertType('stdClass', $this->foo);
		}
	}

}

/** @extends \ArrayObject<int, int> */
class Bag extends \ArrayObject
{

	public ?int $n = null;

	public function doBuiltinMethod(): void
	{
		$this->n = 1;
		$this->count();
		$this->getIterator();
		assertType('1', $this->n);
	}

}

class ChildStateMachine extends StateMachine
{

	public function advance(): bool
	{
		$this->state = 'ready';
		parent::advance();
		assertType("'ready'", $this->state);

		return true;
	}

}
