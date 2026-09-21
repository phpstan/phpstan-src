<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;

#[ReferencedByTurboExtension(key: 'subtractableType')]
interface SubtractableType extends Type
{

	public function getTypeWithoutSubtractedType(): Type;

	public function changeSubtractedType(?Type $subtractedType): Type;

	public function getSubtractedType(): ?Type;

}
