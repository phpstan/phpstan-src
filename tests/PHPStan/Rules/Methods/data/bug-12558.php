<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug12558;

class Foo
{
	/**
	 * @template T of object
	 *
	 * @param T $object
	 *
	 * @return (T is static ? T : static)
	 */
	public static function assertStatic(object $object)
	{
		if (!$object instanceof static) {
			throw new \Error('Object is not an instance of static class');
		}

		return $object;
	}

	protected function createFoo(): self
	{
		return new Foo();
	}

	protected function createFooNullable(): ?self
	{
		return new Foo();
	}

	protected function createFooUnionedWithBool(): self|bool
	{
		return new Foo();
	}

	protected function foo(): void
	{
	}

	public function testAssertInstanceOf(): void
	{
		(static::class)::assertStatic($this->createFoo())->foo();
		(static::class)::assertStatic($this->createFooNullable())->foo();
		(static::class)::assertStatic($this->createFooUnionedWithBool())->foo();
	}
}
