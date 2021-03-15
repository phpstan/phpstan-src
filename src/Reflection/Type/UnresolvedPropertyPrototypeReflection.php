<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Type;

interface UnresolvedPropertyPrototypeReflection
{

	public function doNotResolveTemplateTypeMapToBounds(): self;

	public function getNakedProperty(): PropertyReflection;

	public function getTransformedProperty(): PropertyReflection;

	public function withFechedOnType(Type $type): self;

}
