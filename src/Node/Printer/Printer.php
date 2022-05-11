<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class Printer extends Standard
{

	protected function pPHPStan_Node_TypeExpr(TypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanType(%s)', $expr->getExprType()->describe(VerbosityLevel::precise()));
	}

}
