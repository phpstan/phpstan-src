<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<Exit_>
 */
#[AutowiredService]
final class ExitHandler implements ExprHandler
{

	public function __construct(private ExpressionResultFactory $expressionResultFactory)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Exit_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$kind = $expr->getAttribute('kind', Exit_::KIND_EXIT);
		$identifier = $kind === Exit_::KIND_DIE ? 'die' : 'exit';
		$impurePoints = [
			new ImpurePoint($scope, $expr, $identifier, $identifier, true),
		];

		$hasYield = false;
		$throwPoints = [];
		if ($expr->expr !== null) {
			$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $exprResult->hasYield();
			$throwPoints = $exprResult->getThrowPoints();
			$impurePoints = array_merge($impurePoints, $exprResult->getImpurePoints());
			$scope = $exprResult->getScope();
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: true,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return new NonAcceptingNeverType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
