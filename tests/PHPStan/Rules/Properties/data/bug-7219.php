<?php declare(strict_types=1); // lint >= 7.4

namespace Bug7219;

class Foo
{

	public int $id;
	private ?string $val = null;
	use EmailTrait;
}

trait EmailTrait
{
	protected string $email; // not initialized

	public function getEmail(): string
	{
		return $this->email;
	}


	public function setEmail(string $email): void
	{
		$this->email = $email;
	}
}

$foo = new Foo();
echo $foo->getEmail(); // error Typed property must not be accessed before initialization
echo $foo->id;
