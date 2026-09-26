<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;

/**
 * Object of this class is one of the parameters of `NodeScopeResolver::processStmtNodes()`.
 *
 * It determines whether loops will be analysed once or multiple times
 * until the types "stabilize".
 *
 * When in doubt, use `StatementContext::createTopLevel()`.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/StatementContext.cpp')]
final class StatementContext
{

	private function __construct(
		private bool $isTopLevel,
		private int $foreachUnrollFactor = 1,
		private bool $resolveTemplateArguments = true,
		private ?Type $expectedReturnType = null,
		private ?Type $nativeExpectedReturnType = null,
	)
	{
	}

	/**
	 * @api
	 */
	public static function createTopLevel(bool $resolveTemplateArguments = true): self
	{
		return new self(true, resolveTemplateArguments: $resolveTemplateArguments);
	}

	/**
	 * @api
	 */
	public static function createDeep(bool $resolveTemplateArguments = true): self
	{
		return new self(false, resolveTemplateArguments: $resolveTemplateArguments);
	}

	public function isTopLevel(): bool
	{
		return $this->isTopLevel;
	}

	public function getForeachUnrollFactor(): int
	{
		return $this->foreachUnrollFactor;
	}

	public function shouldResolveTemplateArguments(): bool
	{
		return $this->resolveTemplateArguments;
	}

	public function withoutTemplateArgumentResolution(): self
	{
		if (!$this->resolveTemplateArguments) {
			return $this;
		}

		return new self($this->isTopLevel, $this->foreachUnrollFactor, false, $this->expectedReturnType, $this->nativeExpectedReturnType);
	}

	/**
	 * The type the returned expressions of an anonymous function are expected
	 * to have, from the callable type it is passed to - an expected type for
	 * the closures, arrow functions and array literals it returns.
	 */
	public function withExpectedReturnType(?Type $expectedReturnType, ?Type $nativeExpectedReturnType): self
	{
		if ($expectedReturnType === null && $nativeExpectedReturnType === null) {
			return $this;
		}

		return new self($this->isTopLevel, $this->foreachUnrollFactor, $this->resolveTemplateArguments, $expectedReturnType, $nativeExpectedReturnType);
	}

	public function getExpectedReturnType(): ?Type
	{
		return $this->expectedReturnType;
	}

	public function getNativeExpectedReturnType(): ?Type
	{
		return $this->nativeExpectedReturnType;
	}

	public function enterDeep(): self
	{
		if ($this->isTopLevel) {
			return new self(false, $this->foreachUnrollFactor, $this->resolveTemplateArguments, $this->expectedReturnType, $this->nativeExpectedReturnType);
		}

		return $this;
	}

	public function enterUnrolledForeach(int $totalKeys): self
	{
		return new self($this->isTopLevel, $this->foreachUnrollFactor * $totalKeys, $this->resolveTemplateArguments, $this->expectedReturnType, $this->nativeExpectedReturnType);
	}

}
