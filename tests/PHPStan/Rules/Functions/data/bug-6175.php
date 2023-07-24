<?php

namespace Bug6715Functions;

trait T
{
	/** @return array<mixed> */
	public function getCreateTableSQL(): array
	{
		$sqls = array_merge(
			$this->a(),
			parent::b() // @phpstan-ignore-line
		);

		return $sqls;
	}
}

class A {
	public function a(): mixed {
		throw new \Exception();
	}

	/** @return null */
	public function b() {
		throw new \Exception();
	}
}

class B extends A
{
	use T;
}
