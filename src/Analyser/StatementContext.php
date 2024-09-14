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

	public function enterDeep(): self
	{
		if ($this->isTopLevel) {
			return self::createDeep();
		}

		return $this;
	}

}
