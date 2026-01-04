<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use PhpParser\Node\Expr;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @api
 */
#[AutowiredService]
final class ExprPrinter
{

	public const ATTRIBUTE_CACHE_KEY = 'phpstan_cache_printer';

	public function __construct(private Printer $printer)
	{
	}

	public function printExpr(Expr $expr): string
	{
		/** @var string|null $exprString */
		$exprString = $expr->getAttribute(self::ATTRIBUTE_CACHE_KEY);
		if ($exprString === null) {
			$exprString = $this->printer->prettyPrintExpr($expr);
			$expr->setAttribute(self::ATTRIBUTE_CACHE_KEY, $exprString);
		}

		return $exprString;
	}

}
