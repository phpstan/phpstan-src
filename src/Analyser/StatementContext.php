<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * Object of this class is one of the parameters of `NodeScopeResolver::processStmtNodes()`.
 *
 * It determines whether loops will be analysed once or multiple times
 * until the types "stabilize".
 *
 * When in doubt, use `StatementContext::createTopLevel()`.
 */
final class StatementContext
{

	private function __construct(
		private bool $isTopLevel,
		private int $foreachUnrollFactor = 1,
		private bool $resolveTemplateArguments = true,
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

		return new self($this->isTopLevel, $this->foreachUnrollFactor, false);
	}

	public function enterDeep(): self
	{
		if ($this->isTopLevel) {
			return new self(false, $this->foreachUnrollFactor, $this->resolveTemplateArguments);
		}

		return $this;
	}

	public function enterUnrolledForeach(int $totalKeys): self
	{
		return new self($this->isTopLevel, $this->foreachUnrollFactor * $totalKeys, $this->resolveTemplateArguments);
	}

}
