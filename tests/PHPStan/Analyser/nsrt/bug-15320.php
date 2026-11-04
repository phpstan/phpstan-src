<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15320;

use function PHPStan\Testing\assertType;

class Foo
{
	public function testOutClassVoid(): void
	{
		$m = new Model();

		assert($m->get('name') === 5);
		$m->setVoid('name', 10);
		assertType('int', $m->get('name'));
	}

	public function testOutClassThis(): void
	{
		$m = new Model();

		assert($m->get('name') === 5);
		$m->setThis('name', 10);
		assertType('int', $m->get('name'));
	}

	public function testOutClassStatic(): void
	{
		$m = new Model();

		assert($m->get('name') === 5);
		$m->setStatic('name', 10);
		assertType('5', $m->get('name'));
	}

	public function testOutClassThisParameter(Model $m): void
	{
		assert($m->get('name') === 5);
		$m->setThis('name', 10);
		assertType('int', $m->get('name'));
	}

	public function testOutClassThisArgument(): void
	{
		$m = new Model();
		$other = new Model();

		assert($other->get('name') === 5);
		$m->setThisFromObject($other);
		assertType('5', $other->get('name'));
	}

	public function testOutClassAnnotationMethod(): void
	{
		$m = new MagicModel();

		assert($m->get('name') === 5);
		$m->setMagic('name', 10);
		assertType('int', $m->get('name'));
	}
}

class Model
{
	public function setVoid(string $field, int $value): void
	{
	}

	/**
	 * @return $this
	 */
	public function setThis(string $field, int $value): object
	{
		return $this;
	}

	public function setStatic(string $field, int $value): static
	{
		return $this;
	}

	/**
	 * @return $this
	 */
	public function setThisFromObject(object $other): object
	{
		return $this;
	}

	public function get(string $field): int
	{
		return 1;
	}

	public function testInClassVoid(): void
	{
		$m = $this;

		assert($m->get('name') === 5);
		$m->setVoid('name', 10);
		assertType('int', $m->get('name'));
	}

	public function testInClassThis(): void
	{
		$m = $this;

		assert($m->get('name') === 5);
		$m->setThis('name', 10);
		assertType('int', $m->get('name'));
	}

	public function testInClassStatic(): void
	{
		$m = $this;

		assert($m->get('name') === 5);
		$m->setStatic('name', 10);
		assertType('5', $m->get('name'));
	}
}

/**
 * @method $this setMagic(string $field, int $value)
 */
class MagicModel
{
	public function get(string $field): int
	{
		return 1;
	}

	public function __call(string $name, array $args): mixed
	{
		return $this;
	}
}
