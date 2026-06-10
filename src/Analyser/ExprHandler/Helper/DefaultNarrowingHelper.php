<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * New-world replacement for TypeSpecifier::handleDefaultTruthyOrFalseyContext():
 * the default narrowing of an expression used in a boolean context, computed
 * from the expression's own type (known from its ExpressionResult) instead of
 * Scope::getType().
 */
#[AutowiredService]
final class DefaultNarrowingHelper
{

	public function __construct(private ExprPrinter $exprPrinter)
	{
	}

	public function specifyDefaultTypes(Expr $expr, Type $exprType, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		if (!$context->truthy()) {
			$removedType = StaticTypeFactory::truthy();
		} elseif (!$context->falsey()) {
			$removedType = StaticTypeFactory::falsey();
		} else {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		// mirrors TypeSpecifier::createForExpr() in createFalse() context
		$containsNull = !TypeCombinator::containsNull($removedType) && !$exprType->isNull()->no();

		$originalExpr = $expr;
		if (!$containsNull) {
			$expr = NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($expr);
		}

		$sureNotTypes = [
			$this->exprPrinter->printExpr($expr) => [$expr, $removedType],
		];
		if ($expr !== $originalExpr) {
			$sureNotTypes[$this->exprPrinter->printExpr($originalExpr)] = [$originalExpr, $removedType];
		}

		return (new SpecifiedTypes([], $sureNotTypes))->setRootExpr($originalExpr);
	}

}
