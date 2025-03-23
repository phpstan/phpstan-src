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

class ContainerClass {
	public FinalClass $finalClass;
	public FinalClass $nonFinalClass;

	public Foo $foo;

	public User $user;

	/** @var array<User> */
	public array $arrayOfUsers;
}

function dooNestedUnset(ContainerClass $containerClass) {
	unset($containerClass->finalClass->publicFinalProperty);
	unset($containerClass->finalClass->publicProperty);
	unset($containerClass->finalClass);

	unset($containerClass->nonFinalClass->publicFinalProperty);
	unset($containerClass->nonFinalClass->publicProperty);
	unset($containerClass->nonFinalClass);

	unset($containerClass->foo->iii);
	unset($containerClass->foo);

	unset($containerClass->user->name);
	unset($containerClass->user->fullName);
	unset($containerClass->user);

	unset($containerClass->arrayOfUsers[0]->name);
	unset($containerClass->arrayOfUsers[0]->name);
	unset($containerClass->arrayOfUsers['hans']->fullName);
	unset($containerClass->arrayOfUsers['hans']->fullName);
	unset($containerClass->arrayOfUsers);
}

class Bug12695
{
	/** @var int[] */
	public array $values = [1];
	public function test(): void
	{
		unset($this->values[0]);
	}
}

abstract class Bug12695_AbstractJsonView
{
	protected array $variables = [];

	public function render(): array
	{
		return $this->variables;
	}
}

class Bug12695_GetSeminarDateJsonView extends Bug12695_AbstractJsonView
{
	public function render(): array
	{
		unset($this->variables['settings']);
		return parent::render();
	}
}

class Bug12695_AddBookingsJsonView extends Bug12695_GetSeminarDateJsonView
{
	public function render(): array
	{
		unset($this->variables['seminarDate']);
		return parent::render();
	}
}

class UnsetReadonly
{
	/** @var int[][] */
	public readonly array $a;

	public function doFoo(): void
	{
		unset($this->a[5]);
	}
}
