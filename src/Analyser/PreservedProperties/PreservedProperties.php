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
	 */
	public function __construct(
		private array $propertyNames,
		private array $functionArguments,
		private array $writtenPropertyNames,
		private array $writtenFunctionArguments,
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

}
