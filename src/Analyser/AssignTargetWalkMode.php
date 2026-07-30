<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * How AssignHandler::prepareTarget() walks the assignment target.
 *
 * Besides whether target sub-expressions are walked inside
 * enterExpressionAssign() scopes, the mode says whether the walk also prices
 * the whole target as a read: `$lvalue OP= ...` reads the old value of
 * `$lvalue`, and `$lvalue ??= ...` reads it with isset() semantics (no
 * undefined-variable/uninitialized-property reports; the read carries the
 * isset descriptor). The read happens inside the one target walk instead of
 * callers re-processing the target with a noop callback.
 *
 * @internal
 */
final class AssignTargetWalkMode
{

	private function __construct(
		private bool $enterExpressionAssign,
		private bool $producesTargetReadResult,
		private bool $issetSemanticsForRead,
	)
	{
	}

	public static function assign(): self
	{
		return new self(true, false, false);
	}

	public static function virtualAssign(): self
	{
		return new self(false, false, false);
	}

	public static function readModifyWrite(): self
	{
		return new self(false, true, false);
	}

	public static function coalesceReadModifyWrite(): self
	{
		return new self(true, true, true);
	}

	public function enterExpressionAssign(): bool
	{
		return $this->enterExpressionAssign;
	}

	public function producesTargetReadResult(): bool
	{
		return $this->producesTargetReadResult;
	}

	public function issetSemanticsForRead(): bool
	{
		return $this->issetSemanticsForRead;
	}

}
