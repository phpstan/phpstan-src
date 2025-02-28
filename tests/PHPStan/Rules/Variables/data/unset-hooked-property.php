<?php // lint >= 8.4

namespace UnsetHookedProperty;

function doUnset(Foo $foo, User $user): void {
	unset($user->name);
	unset($user->fullName);

	unset($foo->ii);
	unset($foo->iii);
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

