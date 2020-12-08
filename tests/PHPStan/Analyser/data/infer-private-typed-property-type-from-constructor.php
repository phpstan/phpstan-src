<?php // lint >= 7.4

namespace InferPrivateTypedPropertyTypeFromConstructor;

use function PHPStan\Analyser\assertType;

class Foo
{
	private GenericFoo $genericFoo;
	private int $notIntInConstructorTypehint;
	private int $notIntInConstructorPhpdoc;
	private array $typedArrayPhpdoc;
	private array $typedArray;

	/**
	 * @param string $notIntInConstructorPhpdoc
	 * @param string[] $typedArrayPhpdoc
	 */
	public function __construct(
		$notIntInConstructorPhpdoc,
		string $notIntInConstructorTypehint,
		array $typedArrayPhpdoc,
		string ...$typedArray
	) {
		$this->genericFoo = $this->newGenericFoo(\stdClass::class);
		$this->notIntInConstructorPhpdoc = $notIntInConstructorPhpdoc;
		$this->notIntInConstructorTypehint = $notIntInConstructorTypehint;
		$this->typedArrayPhpdoc = $typedArrayPhpdoc;
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

	public function method()
	{
		assertType('InferPrivateTypedPropertyTypeFromConstructor\GenericFoo<stdClass>', $this->genericFoo);
		assertType('int', $this->notIntInConstructorPhpdoc);
		assertType('int', $this->notIntInConstructorTypehint);
		assertType('array<string>', $this->typedArrayPhpdoc);
		assertType('array<int, string>', $this->typedArray);
	}
}

/**
 * @template T
 */
class GenericFoo
{

}
