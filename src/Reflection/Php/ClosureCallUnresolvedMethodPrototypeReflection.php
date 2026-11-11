<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Type;

#[ReferencedByTurboExtension(key: 'closureCallUnresolvedMethodPrototypeReflection')]
final class ClosureCallUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
{

	/**
	 * @param bool $takesNewThis whether the method takes the object to bind first, like call() and unlike __invoke()
	 */
	public function __construct(private UnresolvedMethodPrototypeReflection $prototype, private ClosureType $closure, private bool $takesNewThis = true)
	{
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedMethodPrototypeReflection
	{
		return new self($this->prototype->doNotResolveTemplateTypeMapToBounds(), $this->closure, $this->takesNewThis);
	}

	public function getNakedMethod(): ExtendedMethodReflection
	{
		return $this->getTransformedMethod();
	}

	public function getTransformedMethod(): ExtendedMethodReflection
	{
		return new ClosureCallMethodReflection($this->prototype->getTransformedMethod(), $this->closure, $this->takesNewThis);
	}

	public function withCalledOnType(Type $type): UnresolvedMethodPrototypeReflection
	{
		return new self($this->prototype->withCalledOnType($type), $this->closure, $this->takesNewThis);
	}

}
