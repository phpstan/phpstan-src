<?php declare(strict_types = 1);

namespace Bug10854;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Bug10854
{
	public function bug10854(string $input): void
	{
		@list(
			$a,
			$b,
			$c,
			$d,
			) = explode(' ', $input);

		assertType('string', $a);
		assertType('string', $b);
		assertType('string', $c);
		assertType('string', $d);

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $c);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $d);

		$d = $d ?? 'unset';
	}
}

class StaticMethodCall {
	/** @return non-empty-list<string> */
	static public function nonEmptyList(): array {
		return ['hello', 'world'];
	}

	public function testStaticList():void {
		@list(
			$a,
			$b,
			) = self::nonEmptyList();

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
	}

	/** @return non-empty-array<string> */
	static public function nonEmptyArray(): array {
		return ['hello', 'world'];
	}

	public function testStaticArray():void {
		@list(
			$a,
			$b,
			) = self::nonEmptyArray();

		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
	}
}

class StaticPropertyFetch {
	/** @var non-empty-list<string> */
	static private $prop = ['hello', 'world'];

	public function testStatic():void {
		@list(
			$a,
			$b,
			) = self::$prop;

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
	}
}

class MethodCall {
	/** @return non-empty-list<string> */
	public function nonEmptyList(): array {
		return ['hello', 'world'];
	}

	public function test():void {
		@list(
			$a,
			$b,
			) = $this->nonEmptyList();

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
	}
}

class PropertyFetch {
	/** @var non-empty-list<string> */
	private $prop = ['hello', 'world'];

	public function test():void {
		@list(
			$a,
			$b,
			) = $this->prop;

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
	}
}

class variants {
	/** @var array<string> */
	private $prop = [];

	public function plainArray():void {
		@list(
			$a,
			$b,
			) = $this->prop;

		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
	}

	/** @var array{string, string} */
	private $shape = ['hello', 'world'];

	public function arrShape():void {
		@list(
			$a,
			$b,
			) = $this->shape;

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createYes(), $b);
	}

	/** @var array{0: int, 1?: int, 2: int} */
	private $shape2 = [0=>123,2=>456];

	public function arrShape2():void {
		// https://3v4l.org/2aIP8
		@list(
			$a,
			$b,
			$c
			) = $this->shape2;

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
		assertVariableCertainty(TrinaryLogic::createYes(), $c);
	}
}
