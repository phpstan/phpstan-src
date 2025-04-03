<?php declare(strict_types = 1);

namespace Bug12382;

class HelloWorld
{
	/** @phpstan-impure */
	public function dummy() : self{
		return $this;
	}
}

class Child extends HelloWorld{
	private int $prop = 1;

	public function dummy() : HelloWorld{
		$this->prop++;
		return $this;
	}
}

final class FinalHelloWorld1
{
	/** @phpstan-impure */
	public function dummy() : self{
		return $this;
	}
}

class FinalHelloWorld2
{
	/** @phpstan-impure */
	final public function dummy() : self{
		return $this;
	}
}

/** @final */
class FinalHelloWorld3
{
	/** @phpstan-impure */
	public function dummy() : self{
		return $this;
	}
}

class FinalHelloWorld4
{
	/**
	 * @final
	 * @phpstan-impure
	 */
	public function dummy() : self{
		return $this;
	}
}
