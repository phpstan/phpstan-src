<?php declare(strict_types = 1);

namespace PHPStan\Analyser\PreservedProperties;

/**
 * Attached by PreservedPropertiesVisitor to a call on $this: the properties of
 * $this the call cannot write, read from the class's own code.
 *
 * Function arguments are properties passed positionally to a named function or to a
 * method of a class named in the call - candidate function names (namespaced first)
 * or 'Class::method', argument position, property name - and written when that
 * parameter is by reference.
 */
final class PreservedProperties
{

	/**
	 * @param array<string, true> $propertyNames kept across a call with side effects
	 * @param list<array{list<non-empty-string>, int, string}> $functionArguments the ones among $propertyNames
	 * @param array<string, true> $writtenPropertyNames written by the class's own code the call runs, which
	 *        a call not known to have side effects forgets
	 * @param list<array{list<non-empty-string>, int, string}> $writtenFunctionArguments the ones that code passes
	 *        and does not write otherwise
	 * @param array<string, true> $closureWrittenPropertyNames written by the class's closures, which whoever
	 *        holds one can run at any time, so the walk of a method does not tell their type after it
	 * @param array<string, array<string, array{list<string>, list<array{list<non-empty-string>, int, string}>}|null>> $overrideWrittenPropertyNames
	 *        the private properties each non-private method of the class writes, by lowercase name - the parent's code can
	 *        run the ones that override its methods whenever it runs - each with the methods it runs that assign it and the
	 *        function arguments it is passed as, or null when it is written in a way no walk sees the value of
	 */
	public function __construct(
		private array $propertyNames,
		private array $functionArguments,
		private array $writtenPropertyNames,
		private array $writtenFunctionArguments,
		private array $closureWrittenPropertyNames,
		private ?string $parentClassName = null,
		private array $overrideWrittenPropertyNames = [],
	)
	{
	}

	/**
	 * @return array<string, true>
	 */
	public function getPropertyNames(): array
	{
		return $this->propertyNames;
	}

	/**
	 * @return list<array{list<non-empty-string>, int, string}>
	 */
	public function getFunctionArguments(): array
	{
		return $this->functionArguments;
	}

	/**
	 * @return array<string, true>
	 */
	public function getWrittenPropertyNames(): array
	{
		return $this->writtenPropertyNames;
	}

	/**
	 * @return list<array{list<non-empty-string>, int, string}>
	 */
	public function getWrittenFunctionArguments(): array
	{
		return $this->writtenFunctionArguments;
	}

	/**
	 * @return array<string, true>
	 */
	public function getClosureWrittenPropertyNames(): array
	{
		return $this->closureWrittenPropertyNames;
	}

	public function getParentClassName(): ?string
	{
		return $this->parentClassName;
	}

	/**
	 * @return array<string, array<string, array{list<string>, list<array{list<non-empty-string>, int, string}>}|null>>
	 */
	public function getOverrideWrittenPropertyNames(): array
	{
		return $this->overrideWrittenPropertyNames;
	}

}
