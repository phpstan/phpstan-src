<?php

namespace Iterables;

use function PHPStan\Testing\assertType;

interface Collection extends \Traversable
{

}

interface CollectionOfIntegers extends \Iterator
{
	public function current(): int;
}

class Foo
{

	/**
	 * @var iterable
	 */
	private $iterableProperty;

	/**
	 * @var string[]|iterable
	 */
	private $stringIterableProperty;

	/**
	 * @var mixed[]|iterable
	 */
	private $mixedIterableProperty;

	/**
	 * @var string[]|iterable|int
	 */
	private $iterablePropertyAlsoWithSomethingElse;

	/**
	 * @var string[]|int[]|iterable|int
	 */
	private $iterablePropertyWithTwoItemTypes;

	/**
	 * @var CollectionOfIntegers|string[]
	 */
	private $collectionOfIntegersOrArrayOfStrings;

	/**
	 * @param iterable $iterableWithIterableTypehint
	 * @param Bar[] $iterableWithConcreteTypehint
	 * @param iterable $arrayWithIterableTypehint
	 * @param Bar[]|Collection $unionIterableType
	 * @param Foo[]|Bar[]|Collection|array $mixedUnionIterableType
	 * @param Bar[]|Collection $unionIterableIterableType
	 * @param int[]|iterable $integers
	 * @param mixed[]|iterable $mixeds
	 * @param \Generator<Foo> $generatorOfFoos
	 * @param \ArrayObject<int, string> $arrayObject
	 */
	public function doFoo(
		iterable $iterableWithoutTypehint,
		iterable $iterableWithIterableTypehint,
		iterable $iterableWithConcreteTypehint,
		array $arrayWithIterableTypehint,
		Collection $unionIterableType,
		array $mixedUnionIterableType,
		iterable $unionIterableIterableType,
		$iterableSpecifiedLater,
		iterable $integers,
		iterable $mixeds,
		$generatorOfFoos,
		$arrayObject
	)
	{
		if (!is_iterable($iterableSpecifiedLater)) {
			return;
		}

		foreach ($iterableWithIterableTypehint as $mixed) {
			foreach ($iterableWithConcreteTypehint as $bar) {
				foreach ($this->doBaz() as $baz) {
					foreach ($unionIterableType as $unionBar) {
						foreach ($mixedUnionIterableType as $mixedBar) {
							foreach ($unionIterableIterableType as $iterableUnionBar) {
								foreach ($this->doUnionIterableWithPhpDoc() as $unionBarFromMethod) {
									foreach ($generatorOfFoos as $fooFromGenerator) {
										foreach ($arrayObject as $arrayObjectKey => $arrayObjectValue) {
											assertType('iterable', $this->iterableProperty);
											assertType('iterable', $iterableSpecifiedLater);
											assertType('iterable', $iterableWithoutTypehint);
											assertType('mixed', $iterableWithoutTypehint[0]);
											assertType('iterable', $iterableWithIterableTypehint);
											assertType('mixed', $iterableWithIterableTypehint[0]);
											assertType('mixed', $mixed);
											assertType('iterable<Iterables\Bar>', $iterableWithConcreteTypehint);
											assertType('mixed', $iterableWithConcreteTypehint[0]);
											assertType('Iterables\Bar', $bar);
											assertType('iterable', $this->doBar());
											assertType('iterable<Iterables\Baz>', $this->doBaz());
											assertType('Iterables\Baz', $baz);
											assertType('array', $arrayWithIterableTypehint);
											assertType('mixed', $arrayWithIterableTypehint[0]);
											assertType('iterable<Iterables\Bar>&Iterables\Collection', $unionIterableType);
											assertType('Iterables\Bar', $unionBar);
											assertType('non-empty-array', $mixedUnionIterableType);
											assertType('iterable<Iterables\Bar>&Iterables\Collection', $unionIterableIterableType);
											assertType('mixed', $mixedBar);
											assertType('Iterables\Bar', $iterableUnionBar);
											assertType('Iterables\Bar', $unionBarFromMethod);
											assertType('iterable<string>', $this->stringIterableProperty);
											assertType('iterable', $this->mixedIterableProperty);
											assertType('iterable<int>', $integers);
											assertType('iterable', $mixeds);
											assertType('iterable', $this->returnIterableMixed());
											assertType('iterable<string>', $this->returnIterableString());
											assertType('int|iterable<string>', $this->iterablePropertyAlsoWithSomethingElse);
											assertType('int|iterable<int|string>', $this->iterablePropertyWithTwoItemTypes);
											assertType('array<string>|Iterables\CollectionOfIntegers', $this->collectionOfIntegersOrArrayOfStrings);
											assertType('Generator<mixed, Iterables\Foo, mixed, mixed>', $generatorOfFoos);
											assertType('Iterables\Foo', $fooFromGenerator);
											assertType('ArrayObject<int, string>', $arrayObject);
											assertType('int', $arrayObjectKey);
											assertType('string', $arrayObjectValue);
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @return iterable
	 */
	public function doBar(): iterable
	{

	}

	/**
	 * @return Baz[]
	 */
	public function doBaz(): iterable
	{

	}

	/**
	 * @return Bar[]|\Traversable
	 */
	public function doUnionIterableWithPhpDoc(): \Traversable
	{

	}

	/**
	 * @return iterable|mixed[]
	 */
	public function returnIterableMixed(): iterable
	{

	}

	/**
	 * @return iterable|string[]
	 */
	public function returnIterableString(): iterable
	{

	}

}
