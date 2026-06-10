<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ConstantTypeHelper;
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

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof PreInc;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $this->resolveTypeFromVarType($expr->var, $scope->getType($expr->var));
	}

	/**
	 * The type of the incremented value, from the variable's own type —
	 * new-world copy of resolveType() usable by both worlds.
	 */
	public function resolveTypeFromVarType(Expr $varExpr, Type $varType): Type
	{
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

				$newTypes[] = ConstantTypeHelper::getTypeFromValue($varValue);
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

		return $this->initializerExprTypeResolver->getPlusType(
			$varExpr,
			new Int_(1),
			static fn (Expr $e): Type => $e === $varExpr ? $varType : ConstantTypeHelper::getTypeFromValue(1),
		);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());

		$typeCallback = function (Expr $e, MutatingScope $s) use ($varResult): Type {
			if (!$e instanceof PreInc) {
				throw new ShouldNotHappenException();
			}

			return $this->resolveTypeFromVarType($e->var, $varResult->getTypeForScope($s));
		};

		$scope = $nodeScopeResolver->processVirtualAssign(
			$varResult->getScope(),
			$storage,
			$stmt,
			$expr->var,
			$expr,
			$nodeCallback,
			$typeCallback,
		)->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $varResult->hasYield(),
			isAlwaysTerminating: $varResult->isAlwaysTerminating(),
			throwPoints: $varResult->getThrowPoints(),
			impurePoints: $varResult->getImpurePoints(),
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
