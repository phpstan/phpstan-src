<?php declare(strict_types = 1);

namespace AssertArglessCallTemplateDefault;

use function PHPStan\Testing\assertType;

class User
{

}

/**
 * @template TKey of array-key
 * @template-covariant TValue
 */
class Collection
{

	/**
	 * @template TFirstDefault
	 *
	 * @param  (callable(TValue, TKey): bool)|null  $callback
	 * @param  TFirstDefault|(\Closure(): TFirstDefault)  $default
	 * @return TValue|TFirstDefault
	 */
	public function first(?callable $callback = null, $default = null)
	{
		throw new \LogicException();
	}

	/**
	 * @template TLastDefault
	 *
	 * @param  (callable(TValue, TKey): bool)|null  $callback
	 * @param  TLastDefault|(\Closure(): TLastDefault)  $default
	 * @return TValue|TLastDefault
	 */
	public function last(?callable $callback = null, $default = null)
	{
		throw new \LogicException();
	}

	/**
	 * @phpstan-assert-if-true null $this->first()
	 * @phpstan-assert-if-true null $this->last()
	 *
	 * @phpstan-assert-if-false TValue $this->first()
	 * @phpstan-assert-if-false TValue $this->last()
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return true;
	}

	/**
	 * @phpstan-assert-if-true TValue $this->first()
	 * @phpstan-assert-if-true TValue $this->last()
	 *
	 * @phpstan-assert-if-false null $this->first()
	 * @phpstan-assert-if-false null $this->last()
	 *
	 * @return bool
	 */
	public function isNotEmpty()
	{
		return true;
	}

}

/**
 * @param Collection<int, User> $collection
 */
function test(Collection $collection): void
{
	assertType('AssertArglessCallTemplateDefault\User|null', $collection->first());
	if ($collection->isNotEmpty()) {
		assertType('AssertArglessCallTemplateDefault\User', $collection->first());
		assertType("'foo'|AssertArglessCallTemplateDefault\User", $collection->first(null, 'foo'));
	} else {
		assertType('null', $collection->first());
		assertType("'foo'|AssertArglessCallTemplateDefault\User", $collection->first(null, 'foo'));
	}
	if ($collection->isEmpty()) {
		assertType('null', $collection->first());
	} else {
		assertType('AssertArglessCallTemplateDefault\User', $collection->first());
	}

	assertType('AssertArglessCallTemplateDefault\User|null', $collection->last());
}
