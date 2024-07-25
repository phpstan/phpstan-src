<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use PhpParser\Node\Expr;

/**
 * @api
 * @final
 */
class ExprPrinter
{

	public function __construct(private Printer $printer)
	{
	}

	public function printExpr(Expr $expr): string
	{
		/** @var string|null $exprString */
		$exprString = $expr->getAttribute('phpstan_cache_printer');
		if ($exprString === null) {
			$exprString = $this->printer->prettyPrintExpr($expr);
			$expr->setAttribute('phpstan_cache_printer', $exprString);
		}

		return $exprString;
	}

}
