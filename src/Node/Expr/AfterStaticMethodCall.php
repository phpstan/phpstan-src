<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

final class AfterStaticMethodCall extends Expr implements VirtualNode
{

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_AfterStaticMethodCall';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
