<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\IncDecTypeHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<PostInc>
 */
#[AutowiredService]
final class PostIncHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private IncDecTypeHelper $incDecTypeHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof PostInc;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());

		// the virtual assign writes the incremented value - hand it the synthetic's
		// result so applyWrite composes off it instead of pricing the
		// unprocessed synthetic (and sentinel comparisons against it) on demand
		$virtualExpr = new PreInc($expr->var);
		$virtualExprResult = $this->expressionResultFactory->create(
			$varResult->getScope(),
			beforeScope: $scope,
			expr: $virtualExpr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			typeCallback: $this->incDecTypeHelper->getTypeCallback($expr->var, $varResult, true),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($virtualExpr, $context),
		);

		// processVirtualAssign() emits nodes (PropertyAssignNode) carrying the
		// synthetic pre-inc/dec as the assigned expression - store its result so
		// rule-side asks about it answer from the storage
		$nodeScopeResolver->storeExpressionResult($storage, $virtualExpr, $virtualExprResult);

		return $this->expressionResultFactory->create(
			$nodeScopeResolver->processVirtualAssign(
				$varResult->getScope(),
				$storage,
				$stmt,
				$expr->var,
				$virtualExpr,
				$nodeCallback,
				$virtualExprResult,
				VariableWrite::KIND_INC_DEC,
			)->getScope(),
			beforeScope: $scope,
			expr: $expr,
			hasYield: $varResult->hasYield(),
			isAlwaysTerminating: $varResult->isAlwaysTerminating(),
			throwPoints: $varResult->getThrowPoints(),
			impurePoints: $varResult->getImpurePoints(),
			// post-increment evaluates to the variable's pre-mutation value
			typeCallback: static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType()),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

}
