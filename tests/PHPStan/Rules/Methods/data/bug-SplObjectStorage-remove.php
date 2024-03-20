<?php declare(strict_types = 1);

namespace BugSplObjectStorageRemove;

class A {}

class B extends A {}

class C extends B {}

class Foo {}

interface BarInterface {}

/** @phpstan-type ObjectStorage \SplObjectStorage<B, int> */
class HelloWorld
{
	/** @var ObjectStorage */
	private \SplObjectStorage $foo;

	/**
	 * @param ObjectStorage $other
	 * @return ObjectStorage
	 */
	public function removeSame(\SplObjectStorage $other): \SplObjectStorage
	{
		$this->foo->removeAll($other);
		$this->foo->removeAllExcept($other);

		return $this->foo;
	}

	/**
	 * @param \SplObjectStorage<C, string> $other
	 * @return ObjectStorage
	 */
	public function removeNarrower(\SplObjectStorage $other): \SplObjectStorage
	{
		$this->foo->removeAll($other);
		$this->foo->removeAllExcept($other);

		return $this->foo;
	}

	/**
	 * @param \SplObjectStorage<B, string> $other
	 * @return ObjectStorage
	 */
	public function removeWider(\SplObjectStorage $other): \SplObjectStorage
	{
		$this->foo->removeAll($other);
		$this->foo->removeAllExcept($other);

		return $this->foo;
	}

	/**
	 * @param \SplObjectStorage<BarInterface, string> $other
	 * @return ObjectStorage
	 */
	public function removePossibleIntersect(\SplObjectStorage $other): \SplObjectStorage
	{
		$this->foo->removeAll($other);
		$this->foo->removeAllExcept($other);

		return $this->foo;
	}

	/**
	 * @param \SplObjectStorage<Foo, string> $other
	 * @return ObjectStorage
	 */
	public function removeNoIntersect(\SplObjectStorage $other): \SplObjectStorage
	{
		$this->foo->removeAll($other);
		$this->foo->removeAllExcept($other);

		return $this->foo;
	}
}
