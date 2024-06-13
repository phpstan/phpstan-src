<?php // onlyif PHP_VERSION_ID >= 80000

namespace Bug6993;

use function PHPStan\Testing\assertType;

/**
 * @template T
 *
 * Generic specification interface
 */
interface SpecificationInterface {
	/**
	 * @param T $specificable
	 */
	public function isSatisfiedBy($specificable): bool;
}

/**
 * @template-extends SpecificationInterface<Foo>
 */
interface FooSpecificationInterface extends SpecificationInterface
{
}

/**
 * Class-conctrete specification
 */
class TestSpecification implements FooSpecificationInterface
{
	public function isSatisfiedBy($specificable): bool
	{
		return true;
	}
}

/**
 * @template TSpecifications of SpecificationInterface<TValue>
 * @template TValue
 * @template-implements SpecificationInterface<TValue>
 */
class AndSpecificationValidator implements SpecificationInterface
{
	/**
	 * @param array<TSpecifications> $specifications
	 */
	public function __construct(private array $specifications)
	{
	}

	public function isSatisfiedBy($specificable): bool
	{
		foreach ($this->specifications as $specification) {
			if (!$specification->isSatisfiedBy($specificable)) {
				return false;
			}
		}

		return true;
	}
}

/**
 * Admitted value for FooSpecificationInterface instances
 */
class Foo
{
}

/**
 * Value not admitted for FooSpecificationInterface instances
 */
class Bar
{
}

function (): void {
	$and = (new AndSpecificationValidator([new TestSpecification()]));
	assertType('Bug6993\AndSpecificationValidator<Bug6993\TestSpecification, Bug6993\Foo>', $and);
	$and->isSatisfiedBy(new Foo());
	$and->isSatisfiedBy(new Bar());
};
