<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Type;

class ClosureCallUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
{

	public function __construct(private UnresolvedMethodPrototypeReflection $prototype, private ClosureType $closure)
	{
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedMethodPrototypeReflection
	{
		return new self($this->prototype->doNotResolveTemplateTypeMapToBounds(), $this->closure);
	}

	public function getNakedMethod(): MethodReflection
	{
		return $this->getTransformedMethod();
	}

	public function getTransformedMethod(): MethodReflection
	{
		return new ClosureCallMethodReflection($this->prototype->getTransformedMethod(), $this->closure);
	}

	public function withCalledOnType(Type $type): UnresolvedMethodPrototypeReflection
	{
		return new self($this->prototype->withCalledOnType($type), $this->closure);
	}

}
