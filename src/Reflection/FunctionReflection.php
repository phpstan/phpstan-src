<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/** @api */
interface FunctionReflection
{

	public function getName(): string;

	public function getFileName(): ?string;

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getVariants(): array;

	public function isDeprecated(): TrinaryLogic;

	public function getDeprecatedDescription(): ?string;

	public function isFinal(): TrinaryLogic;

	public function isInternal(): TrinaryLogic;

	public function getThrowType(): ?Type;

	public function hasSideEffects(): TrinaryLogic;

	public function isBuiltin(): bool;

	public function getAsserts(): Assertions;

	public function getDocComment(): ?string;

}
