<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Reflection for a constant (class constant or global constant).
 *
 * Provides the constant's name, resolved value type, deprecation status, and
 * metadata. This is the base interface — ClassConstantReflection extends it
 * with class-specific features (declaring class, value expression, native type).
 *
 * @api
 */
interface ConstantReflection
{

	public function getName(): string;

	public function getValueType(): Type;

	public function isDeprecated(): TrinaryLogic;

	public function getDeprecatedDescription(): ?string;

	public function isInternal(): TrinaryLogic;

	public function getFileName(): ?string;

	/** @return list<AttributeReflection> */
	public function getAttributes(): array;

}
