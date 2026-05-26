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

	private const LOOP_CONVERGENCE_DEPTH_LIMIT = 2;

	private function __construct(
		private int $depth,
		private int $foreachUnrollFactor = 1,
	)
	{
	}

	/**
	 * @api
	 */
	public static function createTopLevel(): self
	{
		return new self(0);
	}

	/**
	 * @api
	 */
	public static function createDeep(): self
	{
		return new self(self::LOOP_CONVERGENCE_DEPTH_LIMIT);
	}

	public function isTopLevel(): bool
	{
		return $this->depth === 0;
	}

	public function shouldRunLoopConvergence(): bool
	{
		return $this->depth < self::LOOP_CONVERGENCE_DEPTH_LIMIT;
	}

	public function getForeachUnrollFactor(): int
	{
		return $this->foreachUnrollFactor;
	}

	public function enterDeep(): self
	{
		if ($this->depth >= self::LOOP_CONVERGENCE_DEPTH_LIMIT) {
			return $this;
		}

		return new self($this->depth + 1, $this->foreachUnrollFactor);
	}

	public function enterUnrolledForeach(int $totalKeys): self
	{
		return new self($this->depth, $this->foreachUnrollFactor * $totalKeys);
	}

}
