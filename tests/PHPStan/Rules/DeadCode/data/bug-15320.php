<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug15320DeadCode;

class Foo
{
	public function testOutClassVoid(): void
	{
		$m = new Model();

		assert($m->get('name') === 5);
		$m->setVoid('name', 10);
	}

	public function testOutClassThis(): void
	{
		$m = new Model();

		assert($m->get('name') === 5);
		$m->setThis('name', 10);
	}

	public function testOutClassStatic(): void
	{
		$m = new Model();

		assert($m->get('name') === 5);
		$m->setStatic('name', 10);
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

	public function get(string $field): int
	{
		return 1;
	}
}
