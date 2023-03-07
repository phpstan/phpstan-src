<?php

namespace Bug9008;

use function PHPStan\Testing\assertType;

class A {}
class B {}
class C {}

/** @phpstan-type Alpha A|B|C */
class Example
{
	/**
	 * @template TypeAlpha of Alpha
	 * @param TypeAlpha $alpha
	 * @phpstan-return TypeAlpha
	 */
	public function shouldWorkOne(object $alpha): object
	{
		assertType('TypeAlpha of Bug9008\A|Bug9008\B|Bug9008\C (method Bug9008\Example::shouldWorkOne(), argument)', $alpha);
		return $alpha;
	}

	/**
	 * @template TypeAlpha of Alpha
	 * @param TypeAlpha $alpha
	 * @phpstan-return TypeAlpha
	 */
	public function shouldWorkTwo(object $alpha): object
	{
		assertType('TypeAlpha of Bug9008\A|Bug9008\B|Bug9008\C (method Bug9008\Example::shouldWorkTwo(), argument)', $alpha);
		return $alpha;
	}

	/**
	 * @template TypeAlpha of A|B|C
	 * @param TypeAlpha $alpha
	 * @phpstan-return TypeAlpha
	 */
	public function worksButExtraVerboseOne(object $alpha): object
	{
		assertType('TypeAlpha of Bug9008\A|Bug9008\B|Bug9008\C (method Bug9008\Example::worksButExtraVerboseOne(), argument)', $alpha);
		return $alpha;
	}

	/**
	 * @template TypeAlpha of A|B|C
	 * @param TypeAlpha $alpha
	 * @phpstan-return TypeAlpha
	 */
	public function worksButExtraVerboseTwo(object $alpha): object
	{
		assertType('TypeAlpha of Bug9008\A|Bug9008\B|Bug9008\C (method Bug9008\Example::worksButExtraVerboseTwo(), argument)', $alpha);
		return $alpha;
	}

	/**
	 * @param Alpha $alpha
	 */
	public function test(object $alpha): void
	{
		assertType('Bug9008\\A|Bug9008\\B|Bug9008\\C', $alpha);
		assertType('Bug9008\\A|Bug9008\\B|Bug9008\\C', $this->shouldWorkOne($alpha));
		assertType('Bug9008\\A|Bug9008\\B|Bug9008\\C', $this->shouldWorkTwo($alpha));
		assertType('Bug9008\\A|Bug9008\\B|Bug9008\\C', $this->worksButExtraVerboseOne($alpha));
		assertType('Bug9008\\A|Bug9008\\B|Bug9008\\C', $this->worksButExtraVerboseTwo($alpha));
	}
}
