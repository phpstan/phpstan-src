<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

/**
 * The errors expected for data/tentative-return-types.php, shared by
 * OverridingMethodRuleTest and OverridingMethodRuleConfigPhpTest outside a
 * test class: when one test class autoloads another, PHPUnit 9 cannot find
 * the second one in its own file anymore.
 */
final class TentativeReturnTypesErrors
{

	/**
	 * @return list<array{0: string, 1: int, 2?: string}>
	 */
	public static function get(): array
	{
		return [
			[
				'Return type mixed of method TentativeReturnTypes\Foo::getIterator() is not covariant with tentative return type Traversable of method IteratorAggregate<mixed,mixed>::getIterator().',
				8,
				'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
			],
			[
				'Return type string of method TentativeReturnTypes\Lorem::getIterator() is not covariant with tentative return type Traversable of method IteratorAggregate<mixed,mixed>::getIterator().',
				40,
				'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
			],
			[
				'Return type mixed of method TentativeReturnTypes\UntypedIterator::current() is not covariant with tentative return type mixed of method Iterator<mixed,mixed>::current().',
				75,
				'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
			],
			[
				'Return type mixed of method TentativeReturnTypes\UntypedIterator::next() is not covariant with tentative return type void of method Iterator<mixed,mixed>::next().',
				79,
				'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
			],
			[
				'Return type mixed of method TentativeReturnTypes\UntypedIterator::key() is not covariant with tentative return type mixed of method Iterator<mixed,mixed>::key().',
				83,
				'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
			],
			[
				'Return type mixed of method TentativeReturnTypes\UntypedIterator::valid() is not covariant with tentative return type bool of method Iterator<mixed,mixed>::valid().',
				87,
				'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
			],
			[
				'Return type mixed of method TentativeReturnTypes\UntypedIterator::rewind() is not covariant with tentative return type void of method Iterator<mixed,mixed>::rewind().',
				91,
				'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
			],
		];
	}

}
