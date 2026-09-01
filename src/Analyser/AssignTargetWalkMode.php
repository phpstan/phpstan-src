<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Node\Variable\VariableWrite;

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
 * The mode also says whether the write is a source-level write site of a
 * local variable (recorded for the unused-variable check) - a by-ref
 * write-back or a call's scope effect is not.
 *
 * @internal
 */
final class AssignTargetWalkMode
{

	/**
	 * @param VariableWrite::KIND_*|null $writeSiteKind
	 */
	private function __construct(
		private bool $enterExpressionAssign,
		private bool $producesTargetReadResult,
		private bool $issetSemanticsForRead,
		private ?int $writeSiteKind,
	)
	{
	}

	/**
	 * @param VariableWrite::KIND_* $writeSiteKind
	 */
	public static function assign(int $writeSiteKind = VariableWrite::KIND_ASSIGN): self
	{
		return new self(true, false, false, $writeSiteKind);
	}

	/**
	 * @param VariableWrite::KIND_*|null $writeSiteKind
	 */
	public static function virtualAssign(?int $writeSiteKind = null): self
	{
		return new self(false, false, false, $writeSiteKind);
	}

	public static function readModifyWrite(): self
	{
		return new self(false, true, false, VariableWrite::KIND_READ_MODIFY_WRITE);
	}

	public static function coalesceReadModifyWrite(): self
	{
		return new self(true, true, true, VariableWrite::KIND_READ_MODIFY_WRITE);
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

	/**
	 * @return VariableWrite::KIND_*|null
	 */
	public function getWriteSiteKind(): ?int
	{
		return $this->writeSiteKind;
	}

}
