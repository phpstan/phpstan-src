<?php // lint >= 7.4

namespace InferPrivateTypedPropertyTypeFromConstructor;

class Foo
{
	private GenericFoo $genericFoo;
	private array $typedArray;

	public function __construct(string ...$typedArray)
	{
		$this->genericFoo = $this->newGenericFoo(\stdClass::class);
		$this->typedArray = $typedArray;
	}

	/**
	 * @template T
	 *
	 * @param class-string<T> $class
	 *
	 * @return GenericFoo<T>
	 */
	public function newGenericFoo(string $class): GenericFoo
	{
		return new GenericFoo();
	}

	public function doFoo()
	{
		die;
	}
}

/**
 * @template T
 */
class GenericFoo
{

}
