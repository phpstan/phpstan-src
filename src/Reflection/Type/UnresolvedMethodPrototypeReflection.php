<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Type\Type;

interface UnresolvedMethodPrototypeReflection
{

	public function doNotResolveTemplateTypeMapToBounds(): self;

	public function getNakedMethod(): ExtendedMethodReflection;

	public function getTransformedMethod(): ExtendedMethodReflection;

	public function withCalledOnType(Type $type): self;

}
