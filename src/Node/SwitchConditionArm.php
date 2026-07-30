<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;

/**
 * A single non-default `case` of a `switch`, paired with the scope captured
 * right after the case condition was processed (which already excludes the
 * values matched by earlier terminating cases).
 *
 * @api
 */
final class SwitchConditionArm
{

	public function __construct(
		private Expr $caseCondition,
		private Scope $scope,
		private int $line,
		private bool $isLast,
	)
	{
	}

	public function getCaseCondition(): Expr
	{
		return $this->caseCondition;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

	public function getLine(): int
	{
		return $this->line;
	}

	/**
	 * Whether this is the last non-default `case` of the `switch` (only a
	 * `default` may follow it), in which case an always-true comparison is fine
	 * because it does not make any subsequent `case` unreachable. A trailing
	 * `default` is not considered a `case` it would make unreachable.
	 */
	public function isLast(): bool
	{
		return $this->isLast;
	}

}
