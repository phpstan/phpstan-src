<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;

interface UnresolvedMethodPrototypeReflection
{

	public function doNotResolveTemplateTypeMapToBounds(): self;

	public function getNakedMethod(): MethodReflection;

	public function getTransformedMethod(): MethodReflection;

	public function withCalledOnType(Type $type): self;

}
