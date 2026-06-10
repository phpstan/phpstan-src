<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Generator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<YieldFrom>
 */
#[AutowiredService]
final class YieldFromHandler implements ExprHandler
{

	public function __construct(private DefaultNarrowingHelper $defaultNarrowingHelper)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof YieldFrom;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$yieldFromType = $scope->getType($expr->expr);
		$generatorReturnType = $yieldFromType->getTemplateType(Generator::class, 'TReturn');
		if ($generatorReturnType instanceof ErrorType) {
			return new MixedType();
		}

		return $generatorReturnType;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $exprResult->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: true,
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: array_merge($exprResult->getThrowPoints(), [InternalThrowPoint::createImplicit($scope, $expr)]),
			impurePoints: array_merge($exprResult->getImpurePoints(), [new ImpurePoint($scope, $expr, 'yieldFrom', 'yield from', true)]),
			expr: $expr,
			typeCallback: static function (Expr $e, MutatingScope $s) use ($exprResult): Type {
				$yieldFromType = $exprResult->getTypeForScope($s);
				$generatorReturnType = $yieldFromType->getTemplateType(Generator::class, 'TReturn');
				if ($generatorReturnType instanceof ErrorType) {
					return new MixedType();
				}

				return $generatorReturnType;
			},
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
