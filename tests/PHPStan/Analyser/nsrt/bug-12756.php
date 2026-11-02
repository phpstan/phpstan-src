<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12756;

use function PHPStan\Testing\assertType;

/** @template TResult */
abstract class Relation
{
	/** @return TResult */
    public function getResults(): mixed
	{
		return null; // @phpstan-ignore return.type
	}
}

/**
 * @template TRelatedModel of Model
 * @template TPivotModel of Pivot = Pivot
 * @template TAccessor of string = 'pivot'
 * @extends Relation<TRelatedModel&object{TAccessor: TPivotModel}>
 */
class BelongsToMany extends Relation
{
	/** @var TAccessor */
	protected string $accessor = 'pivot';
	/** @var class-string<TPivotModel> */
	protected string $using = Pivot::class;
	
    /**
     * @template TNewPivotModel of Pivot
     * @param  class-string<TNewPivotModel>  $class
     * @return $this
     * @phpstan-this-out static<TRelatedModel, TNewPivotModel, TAccessor>
     */
    public function using(string $class): static
    {
        $this->using = $class; // @phpstan-ignore assign.propertyType
        return $this;
    }

    /**
     * @template TNewAccessor of string
     * @param  TNewAccessor  $accessor
     * @return $this
     * @phpstan-this-out static<TRelatedModel, TPivotModel, TNewAccessor>
     */
    public function as(string $accessor): static
    {
        $this->accessor = $accessor; // @phpstan-ignore assign.propertyType
        return $this;
    }

}

abstract class Model {}
class Pivot extends Model {}
class User extends Model {}

/** @param BelongsToMany<User> $relation */
function test(BelongsToMany $relation): void
{
	assertType('Bug12756\\User&object{pivot: Bug12756\\Pivot}', $relation->getResults());
	assertType('Bug12756\\Pivot', $relation->getResults()->pivot);

	$relation->as('subscription');
	assertType('Bug12756\\User&object{subscription: Bug12756\\Pivot}', $relation->getResults());
	assertType('Bug12756\\Pivot', $relation->getResults()->subscription);
}

class Subscription extends Pivot {}

/** @param BelongsToMany<User, Subscription, 'subscription'> $relation */
function testExplicit(BelongsToMany $relation): void
{
	assertType('Bug12756\\User&object{subscription: Bug12756\\Subscription}', $relation->getResults());
	assertType('Bug12756\\Subscription', $relation->getResults()->subscription);
}
