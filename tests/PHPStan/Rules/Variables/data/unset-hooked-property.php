<?php // lint >= 8.4

namespace UnsetHookedProperty;

function doUnset(Foo $foo, User $user, NonFinalClass $nonFinalClass, FinalClass $finalClass): void {
	unset($user->name);
	unset($user->fullName);

	unset($foo->ii);
	unset($foo->iii);

	unset($nonFinalClass->publicFinalProperty);
	unset($nonFinalClass->publicProperty);

	unset($finalClass->publicFinalProperty);
	unset($finalClass->publicProperty);
}

class User
{
	public string $name {
		set {
			if (strlen($value) === 0) {
				throw new \ValueError("Name must be non-empty");
			}
			$this->name = $value;
		}
	}

	public string $fullName {
		get {
			return "Yennefer of Vengerberg";
		}
	}

	public function __construct(string $name) {
		$this->name = $name;
	}
}

abstract class Foo
{
	abstract protected int $ii { get; }

	abstract public int $iii { get; }
}

class NonFinalClass {
	private string $privateProperty;
	public string $publicProperty;
	final public string $publicFinalProperty;

	function doFoo() {
		unset($this->privateProperty);
	}
}

final class FinalClass {
	private string $privateProperty;
	public string $publicProperty;
	final public string $publicFinalProperty;

	function doFoo() {
		unset($this->privateProperty);
	}
}
