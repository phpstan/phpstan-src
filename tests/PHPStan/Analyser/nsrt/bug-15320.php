<?php declare(strict_types = 1);

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

	public function testOutClassMagicThis(): void
	{
		$m = new Model();

		assert($m->get('name') === 5);
		$m->setMagicThis('name', 10);
		assertType('int', $m->get('name'));
	}

	public function testOutClassParam(Model $m): void
	{
		assert($m->get('name') === 5);
		$m->setThis('name', 10);
		assertType('int', $m->get('name'));
	}
}

/**
 * @method $this setMagicThis(string $field, int $value)
 */
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

	public function get(string $field): int
	{
		return 1;
	}

	/**
	 * @param array<mixed> $args
	 */
	public function __call(string $name, array $args): mixed
	{
		return $this;
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

function testOutClassArgument(Other $o): void
{
	$m = new FluentModel();
	assert($o->get() === 5);
	$m->setOther($o);
	assertType('5', $o->get());
}

class Other
{
	public function get(): int
	{
		return 1;
	}
}

class FluentModel
{
	/**
	 * @return $this
	 */
	public function setOther(Other $o): object
	{
		return $this;
	}

	public function testInClass(Other $o): void
	{
		$m = $this;
		assert($o->get() === 5);
		$m->setOther($o);
		assertType('5', $o->get());
	}
}
