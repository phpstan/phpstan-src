<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15320Rule;

class Foo
{
	public function testOutClass(): void
	{
		$m = new Model();
		$m->setVoid('name', 10);
		$m->setThis('name', 10);
		$m->setStatic('name', 10);
	}

	public function testOutClassParam(Model $m): void
	{
		$m->setVoid('name', 10);
		$m->setThis('name', 10);
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

	public function testInClass(): void
	{
		$m = $this;
		$m->setVoid('name', 10);
		$m->setThis('name', 10);
		$m->setStatic('name', 10);
	}
}
