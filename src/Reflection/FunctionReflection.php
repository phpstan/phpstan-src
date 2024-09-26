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
	 * @return ParametersAcceptorWithPhpDocs[]
	 */
	public function getVariants(): array;

	/**
	 * @internal
	 */
	public function getOnlyVariant(): ParametersAcceptorWithPhpDocs;

	/**
	 * @return ParametersAcceptorWithPhpDocs[]|null
	 */
	public function getNamedArgumentsVariants(): ?array;

	public function acceptsNamedArguments(): bool;

	public function isDeprecated(): TrinaryLogic;

	public function getDeprecatedDescription(): ?string;

	public function isFinal(): TrinaryLogic;

	public function isInternal(): TrinaryLogic;

	public function getThrowType(): ?Type;

	public function hasSideEffects(): TrinaryLogic;

	public function isBuiltin(): bool;

	public function getAsserts(): Assertions;

	public function getDocComment(): ?string;

	public function returnsByReference(): TrinaryLogic;

	/**
	 * This indicates whether the function has phpstan-pure
	 * or phpstan-impure annotation above it.
	 *
	 * In most cases asking hasSideEffects() is much more practical
	 * as it also accounts for void return type (method being always impure).
	 */
	public function isPure(): TrinaryLogic;

}
