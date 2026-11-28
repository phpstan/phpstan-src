<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * Remembers the container of an array offset as it was before the offset was first
 * written, while the following writes keep going to the same offset.
 *
 * The container's value type summarizes all of its offsets, so a write to one offset
 * has to union the written value into it. Writing to the same offset again would also
 * keep the value the offset held after the previous write, although that value is
 * gone. Writing into this remembered container instead keeps only what the other
 * offsets hold. Reassigning the container or any part of the offset expression
 * invalidates it like any expression that contains them.
 */
#[ReferencedByTurboExtension(key: 'offsetWriteBaseExpr')]
final class OffsetWriteBaseExpr extends Expr implements VirtualNode
{

	public function __construct(public ArrayDimFetch $dimFetch)
	{
		parent::__construct([]);
	}

	public function getDimFetch(): ArrayDimFetch
	{
		return $this->dimFetch;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_OffsetWriteBaseExpr';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return ['dimFetch'];
	}

}
