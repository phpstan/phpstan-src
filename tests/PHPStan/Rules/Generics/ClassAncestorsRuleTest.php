<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ClassAncestorsRule>
 */
class ClassAncestorsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ClassAncestorsRule(
			new GenericAncestorsCheck(
				$this->createReflectionProvider(),
				new GenericObjectTypeCheck(),
				new VarianceCheck(true, true),
				true,
				[],
			),
			new CrossCheckInterfacesHelper(),
		);
	}

	public function testRuleExtends(): void
	{
		$this->analyse([__DIR__ . '/data/class-ancestors-extends.php'], [
			[
				'Class ClassAncestorsExtends\FooDoesNotExtendAnything has @extends tag, but does not extend any class.',
				26,
			],
			[
				'The @extends tag of class ClassAncestorsExtends\FooDuplicateExtendsTags describes ClassAncestorsExtends\FooGeneric2 but the class extends ClassAncestorsExtends\FooGeneric.',
				35,
			],
			[
				'The @extends tag of class ClassAncestorsExtends\FooWrongClassExtended describes ClassAncestorsExtends\FooGeneric2 but the class extends ClassAncestorsExtends\FooGeneric.',
				43,
			],
			[
				'Class ClassAncestorsExtends\FooWrongClassExtended extends generic class ClassAncestorsExtends\FooGeneric but does not specify its types: T, U',
				43,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Class ClassAncestorsExtends\FooWrongTypeInExtendsTag @extends tag contains incompatible type class-string<ClassAncestorsExtends\T>.',
				51,
			],
			[
				'Class ClassAncestorsExtends\FooWrongTypeInExtendsTag extends generic class ClassAncestorsExtends\FooGeneric but does not specify its types: T, U',
				51,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Generic type ClassAncestorsExtends\FooGeneric<int> in PHPDoc tag @extends does not specify all template types of class ClassAncestorsExtends\FooGeneric: T, U',
				67,
			],
			[
				'Generic type ClassAncestorsExtends\FooGeneric<int, InvalidArgumentException, string> in PHPDoc tag @extends specifies 3 template types, but class ClassAncestorsExtends\FooGeneric supports only 2: T, U',
				75,
			],
			[
				'Type Throwable in generic type ClassAncestorsExtends\FooGeneric<int, Throwable> in PHPDoc tag @extends is not subtype of template type U of Exception of class ClassAncestorsExtends\FooGeneric.',
				83,
			],
			[
				'Type stdClass in generic type ClassAncestorsExtends\FooGeneric<int, stdClass> in PHPDoc tag @extends is not subtype of template type U of Exception of class ClassAncestorsExtends\FooGeneric.',
				91,
			],
			[
				'PHPDoc tag @extends has invalid type ClassAncestorsExtends\Zazzuuuu.',
				99,
			],
			[
				'Type mixed in generic type ClassAncestorsExtends\FooGeneric<int, mixed> in PHPDoc tag @extends is not subtype of template type U of Exception of class ClassAncestorsExtends\FooGeneric.',
				108,
			],
			[
				'Type Throwable in generic type ClassAncestorsExtends\FooGeneric<int, Throwable> in PHPDoc tag @extends is not subtype of template type U of Exception of class ClassAncestorsExtends\FooGeneric.',
				117,
			],
			[
				'Type stdClass in generic type ClassAncestorsExtends\FooGeneric<int, stdClass> in PHPDoc tag @extends is not subtype of template type U of Exception of class ClassAncestorsExtends\FooGeneric.',
				163,
			],
			[
				'Class ClassAncestorsExtends\FooExtendsGenericClass extends generic class ClassAncestorsExtends\FooGeneric but does not specify its types: T, U',
				174,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Template type T is declared as covariant, but occurs in invariant position in extended type ClassAncestorsExtends\FooGeneric8<T, T> of class ClassAncestorsExtends\FooGeneric9.',
				192,
			],
			[
				'Template type T is declared as contravariant, but occurs in covariant position in extended type ClassAncestorsExtends\FooGeneric8<T, T> of class ClassAncestorsExtends\FooGeneric10.',
				201,
			],
			[
				'Template type T is declared as contravariant, but occurs in invariant position in extended type ClassAncestorsExtends\FooGeneric8<T, T> of class ClassAncestorsExtends\FooGeneric10.',
				201,
			],
			[
				'Class ClassAncestorsExtends\FilterIteratorChild extends generic class FilterIterator but does not specify its types: TKey, TValue, TIterator',
				215,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Class ClassAncestorsExtends\FooObjectStorage @extends tag contains incompatible type ClassAncestorsExtends\FooObjectStorage.',
				226,
			],
			[
				'Class ClassAncestorsExtends\FooObjectStorage extends generic class SplObjectStorage but does not specify its types: TObject, TData',
				226,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Class ClassAncestorsExtends\FooCollection @extends tag contains incompatible type ClassAncestorsExtends\FooCollection&iterable<int>.',
				239,
			],
			[
				'Class ClassAncestorsExtends\FooCollection extends generic class ClassAncestorsExtends\AbstractFooCollection but does not specify its types: T',
				239,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Call-site variance annotation of covariant Throwable in generic type ClassAncestorsExtends\FooGeneric<covariant Throwable, InvalidArgumentException> in PHPDoc tag @extends is not allowed.',
				246,
			],
		]);
	}

	public function testRuleImplements(): void
	{
		$this->analyse([__DIR__ . '/data/class-ancestors-implements.php'], [
			[
				'Class ClassAncestorsImplements\FooDoesNotImplementAnything has @implements tag, but does not implement any interface.',
				35,
			],
			[
				'The @implements tag of class ClassAncestorsImplements\FooInvalidImplementsTags describes ClassAncestorsImplements\FooGeneric2 but the class implements: ClassAncestorsImplements\FooGeneric',
				44,
			],
			[
				'The @implements tag of class ClassAncestorsImplements\FooWrongClassImplemented describes ClassAncestorsImplements\FooGeneric2 but the class implements: ClassAncestorsImplements\FooGeneric, ClassAncestorsImplements\FooGeneric3',
				52,
			],
			[
				'Class ClassAncestorsImplements\FooWrongClassImplemented implements generic interface ClassAncestorsImplements\FooGeneric but does not specify its types: T, U',
				52,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Class ClassAncestorsImplements\FooWrongClassImplemented implements generic interface ClassAncestorsImplements\FooGeneric3 but does not specify its types: T, W',
				52,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Class ClassAncestorsImplements\FooWrongTypeInImplementsTag @implements tag contains incompatible type class-string<ClassAncestorsImplements\T>.',
				60,
			],
			[
				'Class ClassAncestorsImplements\FooWrongTypeInImplementsTag implements generic interface ClassAncestorsImplements\FooGeneric but does not specify its types: T, U',
				60,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Generic type ClassAncestorsImplements\FooGeneric<int> in PHPDoc tag @implements does not specify all template types of interface ClassAncestorsImplements\FooGeneric: T, U',
				76,
			],
			[
				'Generic type ClassAncestorsImplements\FooGeneric<int, InvalidArgumentException, string> in PHPDoc tag @implements specifies 3 template types, but interface ClassAncestorsImplements\FooGeneric supports only 2: T, U',
				84,
			],
			[
				'Type Throwable in generic type ClassAncestorsImplements\FooGeneric<int, Throwable> in PHPDoc tag @implements is not subtype of template type U of Exception of interface ClassAncestorsImplements\FooGeneric.',
				92,
			],
			[
				'Type stdClass in generic type ClassAncestorsImplements\FooGeneric<int, stdClass> in PHPDoc tag @implements is not subtype of template type U of Exception of interface ClassAncestorsImplements\FooGeneric.',
				100,
			],
			[
				'PHPDoc tag @implements has invalid type ClassAncestorsImplements\Zazzuuuu.',
				108,
			],
			[
				'Type mixed in generic type ClassAncestorsImplements\FooGeneric<int, mixed> in PHPDoc tag @implements is not subtype of template type U of Exception of interface ClassAncestorsImplements\FooGeneric.',
				117,
			],
			[
				'Type Throwable in generic type ClassAncestorsImplements\FooGeneric<int, Throwable> in PHPDoc tag @implements is not subtype of template type U of Exception of interface ClassAncestorsImplements\FooGeneric.',
				126,
			],
			[
				'Type stdClass in generic type ClassAncestorsImplements\FooGeneric<int, stdClass> in PHPDoc tag @implements is not subtype of template type U of Exception of interface ClassAncestorsImplements\FooGeneric.',
				172,
			],
			[
				'Type stdClass in generic type ClassAncestorsImplements\FooGeneric<int, stdClass> in PHPDoc tag @implements is not subtype of template type U of Exception of interface ClassAncestorsImplements\FooGeneric.',
				182,
			],
			[
				'Type stdClass in generic type ClassAncestorsImplements\FooGeneric2<int, stdClass> in PHPDoc tag @implements is not subtype of template type V of Exception of interface ClassAncestorsImplements\FooGeneric2.',
				182,
			],
			[
				'Class ClassAncestorsImplements\FooImplementsGenericInterface implements generic interface ClassAncestorsImplements\FooGeneric but does not specify its types: T, U',
				198,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Template type T is declared as covariant, but occurs in invariant position in implemented type ClassAncestorsImplements\FooGeneric9<T, T> of class ClassAncestorsImplements\FooGeneric10.',
				216,
			],
			[
				'Class ClassAncestorsImplements\FooIterator @implements tag contains incompatible type ClassAncestorsImplements\FooIterator&iterable<int, object>.',
				222,
			],
			[
				'Class ClassAncestorsImplements\FooIterator implements generic interface Iterator but does not specify its types: TKey, TValue',
				222,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Class ClassAncestorsImplements\FooCollection @implements tag contains incompatible type ClassAncestorsImplements\FooCollection&iterable<int>.',
				235,
			],
			[
				'Class ClassAncestorsImplements\FooCollection implements generic interface ClassAncestorsImplements\AbstractFooCollection but does not specify its types: T',
				235,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Call-site variance annotation of covariant Throwable in generic type ClassAncestorsImplements\FooGeneric<covariant Throwable, InvalidArgumentException> in PHPDoc tag @implements is not allowed.',
				242,
			],
		]);
	}

	public function testBug3922(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3922-ancestors.php'], [
			[
				'Type Bug3922Ancestors\BarQuery in generic type Bug3922Ancestors\QueryHandlerInterface<string, Bug3922Ancestors\BarQuery> in PHPDoc tag @implements is not subtype of template type TQuery of Bug3922Ancestors\QueryInterface<string> of interface Bug3922Ancestors\QueryHandlerInterface.',
				54,
			],
		]);
	}

	public function testBug3922Reversed(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3922-ancestors-reversed.php'], [
			[
				'Type string in generic type Bug3922AncestorsReversed\QueryHandlerInterface<Bug3922AncestorsReversed\BarQuery, string> in PHPDoc tag @implements is not subtype of template type int of interface Bug3922AncestorsReversed\QueryHandlerInterface.',
				54,
			],
		]);
	}

	public function testCrossCheckInterfaces(): void
	{
		$this->analyse([__DIR__ . '/data/cross-check-interfaces.php'], [
			[
				'Interface IteratorAggregate specifies template type TValue of interface Traversable as string but it\'s already specified as CrossCheckInterfaces\Item.',
				19,
			],
		]);
	}

	public function testScalarClassName(): void
	{
		$this->analyse([__DIR__ . '/data/scalar-class-name.php'], []);
	}

	public function testBug8473(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8473.php'], []);
	}

}
