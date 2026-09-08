<?php // lint >= 8.0

namespace NullsafeMethodCallTemplateType;

class Foo
{

	public function getValue(): ?string
	{
		return null;
	}

}

/**
 * @template T
 * @param T $foo
 */
function foo($foo)
{
	if ($foo !== null && !$foo instanceof Foo) {
		throw new \Exception();
	}

	return $foo?->getValue();
}
