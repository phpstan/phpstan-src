<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug9445;

class Foo
{
	public int $id;
	public null|self $parent;

	public function contains(self $foo): bool
	{
		do {
			if ($this->id === $foo->id) {
				return true;
			}
		} while (!is_null($foo = $foo->parent));

		return false;
	}
}
