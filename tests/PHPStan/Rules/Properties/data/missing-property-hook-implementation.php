<?php declare(strict_types = 1); // lint >= 8.4

namespace MissingPropertyHookImplementation;

interface RequiresGet
{
	public string $name { get; }
}

final class MissingGet implements RequiresGet
{
}

interface RequiresSet
{
	public string $name { set; }
}

final class MissingSet implements RequiresSet
{
}

abstract class AbstractBase
{
	abstract public int $id { get; set; }
}

final class MissingBoth extends AbstractBase
{
}

abstract class AbstractChild extends AbstractBase
{
}

trait RequiresFromTrait
{
	abstract public bool $active { get; }
}

final class MissingTraitHook
{
	use RequiresFromTrait;
}

final class ImplementsWithProperty implements RequiresGet
{
	public string $name;
}

final class ImplementsWithHooks extends AbstractBase
{
	public int $id {
		get => 1;
		set { }
	}
}

new class () implements RequiresGet
{
};
