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
		private bool $insideUnrolledForeach = false,
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

	public function isInsideUnrolledForeach(): bool
	{
		return $this->insideUnrolledForeach;
	}

	public function enterDeep(): self
	{
		if ($this->isTopLevel) {
			return new self(false, $this->insideUnrolledForeach);
		}

		return $this;
	}

	public function enterUnrolledForeach(): self
	{
		return new self($this->isTopLevel, true);
	}

}
