<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/** @api */
interface GlobalConstantReflection
{

	public function getName(): string;

	public function getValueType(): Type;

	public function isDeprecated(): TrinaryLogic;

	public function getDeprecatedDescription(): ?string;

	public function isInternal(): TrinaryLogic;

	public function getFileName(): ?string;

}
