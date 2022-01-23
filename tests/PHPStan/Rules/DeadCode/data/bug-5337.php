<?php // lint >= 7.4

namespace Bug5337;

class Clazz
{
	private ?string $prefix;

	public function setter(string $prefix): void
	{
		if (!empty($this->prefix)) {
			$this->prefix = $prefix;
		}
	}
}

class Foo
{

	private string $field;

	public function __construct()
	{
		if (isset($this->field)) {}
	}

}
