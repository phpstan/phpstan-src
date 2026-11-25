<?php // lint >= 8.1

namespace Bug9326;

class HelloWorld
{
	private readonly string $foo;
	public function __construct()
	{
		$this->onlyUsedInConstructor();
	}

	private function onlyUsedInConstructor(): void
	{
		// complicated stuff

		$this->foo = 'bar';
	}

	public function getFoo(): string
	{
		return $this->foo;
	}
}

class PrivateAlsoCalledElsewhere
{
	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	public function reset(): void
	{
		$this->init();
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}

final class ProtectedInFinalClass
{
	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	protected function init(): void
	{
		$this->foo = 'bar';
	}
}

class ProtectedInNonFinalClass
{
	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	protected function init(): void
	{
		$this->foo = 'bar';
	}
}

final class PublicInFinalClass
{
	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	public function init(): void
	{
		$this->foo = 'bar';
	}
}

abstract class ParentDeclaringInit
{
	public function reset(): void
	{
		$this->init();
	}

	abstract protected function init(): void;
}

final class ProtectedInFinalClassOverridingParent extends ParentDeclaringInit
{
	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	protected function init(): void
	{
		$this->foo = 'bar';
	}
}

class ThroughOtherPrivateMethods
{
	private readonly string $foo;

	public function __construct()
	{
		self::first();
	}

	private function first(): void
	{
		$this->second();
	}

	private function second(): void
	{
		$this->foo = 'bar';
	}
}

class ThroughPrivateMethodAlsoCalledElsewhere
{
	private readonly string $foo;

	public function __construct()
	{
		$this->first();
	}

	public function reset(): void
	{
		$this->first();
	}

	private function first(): void
	{
		$this->second();
	}

	private function second(): void
	{
		$this->foo = 'bar';
	}
}

class MutualRecursionFromConstructor
{
	private readonly string $foo;

	public function __construct()
	{
		$this->even(2);
	}

	private function even(int $i): void
	{
		if ($i === 0) {
			$this->foo = 'bar';
			return;
		}
		$this->odd($i - 1);
	}

	private function odd(int $i): void
	{
		$this->even($i - 1);
	}
}

class NeverCalled
{
	private readonly string $foo;

	public function __construct()
	{
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}

	private function initOther(): void
	{
		$this->initAgain();
	}

	private function initAgain(): void
	{
		$this->foo = 'bar';
		$this->initOther();
	}
}

class CalledInClosure
{
	private readonly string $foo;

	public function __construct()
	{
		$f = function (): void {
			$this->init();
		};
		$f();
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}

class FirstClassCallable
{
	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	public function getInit(): \Closure
	{
		return $this->init(...);
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}

class ArrayCallable
{
	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	public function getInit(): callable
	{
		return [$this, 'init'];
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}

class DynamicCall
{
	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	public function call(string $method): void
	{
		$this->$method();
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}

class CalledOnOtherObject
{
	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	public static function create(): self
	{
		$self = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
		$self->init();

		return $self;
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}

class AssignsOtherObject
{
	private readonly string $foo;

	public function __construct(self $other)
	{
		$this->foo = 'foo';
		$this->init($other);
	}

	private function init(self $other): void
	{
		$other->foo = 'bar';
	}
}

trait CallsInit
{
	public function reset(): void
	{
		$this->init();
	}
}

class UsesTrait
{
	use CallsInit;

	private readonly string $foo;

	public function __construct()
	{
		$this->init();
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}

class TestCase
{
	protected function setUp(): void
	{
	}
}

class AdditionalConstructor extends TestCase
{
	private readonly string $foo;

	protected function setUp(): void
	{
		$this->init();
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}
