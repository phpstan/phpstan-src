<?php // lint >= 8.5

namespace RememberReadonlyCloneWithClassAware;

use function PHPStan\Testing\assertType;

// A final class that contains no "clone with" expression cannot have its own readonly
// properties reinitialized, so the constructor-narrowed types are kept.
final class FinalWithoutCloneWith
{
	private readonly int $i;

	public function __construct()
	{
		if (rand(0, 1)) {
			$this->i = 4;
		} else {
			$this->i = 10;
		}
	}

	public function doFoo(): void
	{
		assertType('4|10', $this->i);
	}
}

// A final class that uses "clone with" can reinitialize its readonly properties, so the
// narrowed types are widened back to the declared type.
final class FinalWithCloneWith
{
	private readonly int $i;

	public function __construct()
	{
		if (rand(0, 1)) {
			$this->i = 4;
		} else {
			$this->i = 10;
		}
	}

	public function withI(int $i): self
	{
		return clone($this, ['i' => $i]);
	}

	public function doFoo(): void
	{
		assertType('int', $this->i);
	}
}

// A non-final class might be extended by a subclass we cannot see, so stay conservative
// and widen the readonly property.
class NonFinal
{
	private readonly int $i;

	public function __construct()
	{
		if (rand(0, 1)) {
			$this->i = 4;
		} else {
			$this->i = 10;
		}
	}

	public function doFoo(): void
	{
		assertType('int', $this->i);
	}
}

final class FinalSubclass extends NonFinal
{
	private readonly int $j;

	public function __construct()
	{
		parent::__construct();
		if (rand(0, 1)) {
			$this->j = 4;
		} else {
			$this->j = 10;
		}
	}

	public function doFoo(): void
	{
		// own readonly property is remembered
		assertType('4|10', $this->j);
	}
}
