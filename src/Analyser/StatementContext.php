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
	)
	{
	}

	/**
	 * @api
	 */
	public static function createTopLevel(): self
	{
		return new self(true);
	}

	/**
	 * @api
	 */
	public static function createDeep(): self
	{
		return new self(false);
	}

	public function isTopLevel(): bool
	{
		return $this->isTopLevel;
	}

	public function getForeachUnrollFactor(): int
	{
		return $this->foreachUnrollFactor;
	}

	public function enterDeep(): self
	{
		if ($this->isTopLevel) {
			return new self(false, $this->foreachUnrollFactor);
		}

		return $this;
	}

	public function enterUnrolledForeach(int $totalKeys): self
	{
		return new self($this->isTopLevel, $this->foreachUnrollFactor * $totalKeys);
	}

}
