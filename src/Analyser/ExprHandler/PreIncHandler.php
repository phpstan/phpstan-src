<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ValueError;
use function count;
use function is_bool;
use function is_numeric;
use function is_string;
use function str_increment;

/**
 * @implements ExprHandler<PreInc>
 */
#[AutowiredService]
final class PreIncHandler implements ExprHandler
{

	public function __construct(private ExpressionResultFactory $expressionResultFactory)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof PreInc;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$varType = $scope->getType($expr->var);
		$varScalars = $varType->getConstantScalarValues();

		if (count($varScalars) > 0) {
			$newTypes = [];

			foreach ($varScalars as $varValue) {
				if ($varValue === '') {
					$varValue = '1';
				} elseif (is_string($varValue) && !is_numeric($varValue)) {
					try {
						$varValue = str_increment($varValue);
					} catch (ValueError) {
						return new NeverType();
					}
				} elseif (!is_bool($varValue)) {
					++$varValue;
				}

				$newTypes[] = $scope->getTypeFromValue($varValue);
			}
			return TypeCombinator::union(...$newTypes);
		} elseif ($varType->isString()->yes()) {
			if ($varType->isLiteralString()->yes()) {
				return new IntersectionType([
					new StringType(),
					new AccessoryLiteralStringType(),
				]);
			}

			if ($varType->isNumericString()->yes()) {
				return new BenevolentUnionType([
					new IntegerType(),
					new FloatType(),
				]);
			}

			return new BenevolentUnionType([
				new StringType(),
				new IntegerType(),
				new FloatType(),
			]);
		}

		return $scope->getType(new Plus($expr->var, new Int_(1)));
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());

		$scope = $nodeScopeResolver->processVirtualAssign(
			$varResult->getScope(),
			$storage,
			$stmt,
			$expr->var,
			$expr,
			$nodeCallback,
		)->getScope();

		return $this->expressionResultFactory->create(
			$scope,
			hasYield: $varResult->hasYield(),
			isAlwaysTerminating: $varResult->isAlwaysTerminating(),
			throwPoints: $varResult->getThrowPoints(),
			impurePoints: $varResult->getImpurePoints(),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
