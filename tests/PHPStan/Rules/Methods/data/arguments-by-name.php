<?php // lint >= 8.0

namespace ArgumentsByNameMethod;

class Foo
{

	/**
	 * @optional-arguments-by-name
	 */
	public function optional(string $dialect, string $group = 'default', bool $allowExtraAttributes = true): void
	{

	}

	/**
	 * @arguments-by-name
	 */
	public function all(string $dialect, string $group = 'default'): void
	{

	}

	/**
	 * @stable-parameter-names
	 */
	public function stable(string $dialect, string $group = 'default'): void
	{

	}

	/**
	 * @no-named-arguments
	 * @optional-arguments-by-name
	 */
	public function conflicting(string $dialect, string $group = 'default'): void
	{

	}

}

/**
 * @optional-arguments-by-name
 */
class ClassLevel
{

	public function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}

class Child extends Foo
{

	public function optional(string $dialect, string $group = 'default', bool $allowExtraAttributes = true): void
	{

	}

	public function all(string $dialect, string $group = 'default'): void
	{

	}

}

interface FooInterface
{

	/**
	 * @arguments-by-name
	 */
	public function doFoo(string $dialect, string $group = 'default'): void;

}

class FooImplementation implements FooInterface
{

	public function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}

function (Foo $foo, ClassLevel $classLevel, Child $child, FooInterface $interface, FooImplementation $implementation): void {
	$foo->optional('openapi-3.1', group: 'read');
	$foo->optional('openapi-3.1', 'read', false);
	$foo->all(dialect: 'openapi-3.1', group: 'read');
	$foo->all('openapi-3.1', group: 'read');
	$foo->stable('openapi-3.1', 'read');
	$foo->conflicting('openapi-3.1', 'read');

	$classLevel->doFoo('openapi-3.1', group: 'read');
	$classLevel->doFoo('openapi-3.1', 'read');

	$child->optional('openapi-3.1', 'read');
	$child->all('openapi-3.1');

	$interface->doFoo('openapi-3.1');
	$implementation->doFoo('openapi-3.1');
	$implementation->doFoo(dialect: 'openapi-3.1');
};

/**
 * @no-named-arguments
 */
class NoNamedArgumentsByDefault
{

	/**
	 * @arguments-by-name
	 */
	public function all(string $dialect, string $group = 'default'): void
	{

	}

	/**
	 * @optional-arguments-by-name
	 */
	public function optional(string $dialect, string $group = 'default'): void
	{

	}

	/**
	 * @stable-parameter-names
	 */
	public function stable(string $dialect, string $group = 'default'): void
	{

	}

	public function doDefault(string $dialect): void
	{

	}

}

/**
 * @arguments-by-name
 */
class ArgumentsByNameByDefault
{

	/**
	 * @optional-arguments-by-name
	 */
	public function optional(string $dialect, string $group = 'default'): void
	{

	}

	/**
	 * @no-named-arguments
	 */
	public function noNamed(string $dialect): void
	{

	}

}

class ChildOfClassLevel extends ClassLevel
{

	public function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}

/**
 * @arguments-by-name
 */
interface ClassLevelInterface
{

	public function doFoo(string $dialect, string $group = 'default'): void;

}

class ClassLevelInterfaceImplementation implements ClassLevelInterface
{

	public function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}

class NoNamedArgumentsParent
{

	/**
	 * @no-named-arguments
	 */
	public function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}

class ArgumentsByNameChild extends NoNamedArgumentsParent
{

	/**
	 * @arguments-by-name
	 */
	public function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}

function (
	NoNamedArgumentsByDefault $noNamedByDefault,
	ArgumentsByNameByDefault $byNameByDefault,
	ChildOfClassLevel $childOfClassLevel,
	ClassLevelInterfaceImplementation $classLevelImplementation,
	ArgumentsByNameChild $byNameChild,
): void {
	$noNamedByDefault->all(dialect: 'openapi-3.1', group: 'read');
	$noNamedByDefault->all('openapi-3.1');
	$noNamedByDefault->optional('openapi-3.1', group: 'read');
	$noNamedByDefault->optional('openapi-3.1', 'read');
	$noNamedByDefault->stable(dialect: 'openapi-3.1', group: 'read');
	$noNamedByDefault->stable('openapi-3.1', 'read');
	$noNamedByDefault->doDefault(dialect: 'openapi-3.1');

	$byNameByDefault->optional('openapi-3.1', group: 'read');
	$byNameByDefault->optional('openapi-3.1', 'read');
	$byNameByDefault->noNamed('openapi-3.1');
	$byNameByDefault->noNamed(dialect: 'openapi-3.1');

	$childOfClassLevel->doFoo('openapi-3.1', 'read');
	$classLevelImplementation->doFoo('openapi-3.1');
	$byNameChild->doFoo(dialect: 'openapi-3.1');
	$byNameChild->doFoo('openapi-3.1');
};

class ChildOfClassLevelWithNewMethod extends ClassLevel
{

	public function doBar(string $dialect, string $group = 'default'): void
	{

	}

}

class PrivateTagged
{

	/**
	 * @arguments-by-name
	 */
	private function doFoo(string $dialect): void
	{

	}

}

class ChildOfPrivateTagged extends PrivateTagged
{

	public function doFoo(string $dialect): void
	{

	}

}

/**
 * @no-named-arguments
 * @arguments-by-name
 */
class ConflictingClassLevel
{

	public function doFoo(string $dialect): void
	{

	}

}

class StableParameterNamesChild extends NoNamedArgumentsParent
{

	/**
	 * @stable-parameter-names
	 */
	public function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}

function (
	ChildOfClassLevelWithNewMethod $childWithNewMethod,
	ChildOfPrivateTagged $childOfPrivateTagged,
	ConflictingClassLevel $conflictingClassLevel,
	StableParameterNamesChild $stableChild,
): void {
	$childWithNewMethod->doBar('openapi-3.1', 'read');
	$childOfPrivateTagged->doFoo('openapi-3.1');
	$conflictingClassLevel->doFoo('openapi-3.1');
	$conflictingClassLevel->doFoo(dialect: 'openapi-3.1');
	$stableChild->doFoo(dialect: 'openapi-3.1', group: 'read');
	$stableChild->doFoo('openapi-3.1', 'read');
};

/**
 * @arguments-by-name
 */
class ClassLevelWithPrivateMethod
{

	public function doFoo(string $dialect): void
	{
		$this->doBar($dialect);
	}

	private function doBar(string $dialect): void
	{

	}

}
