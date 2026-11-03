<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use Closure;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\PartialFunctionApplicationNode;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * The bound arguments of a partial function application are evaluated when
 * the closure is created, so they are processed here, after the callee.
 *
 * @implements ExprHandler<PartialFunctionApplicationNode>
 */
#[AutowiredService]
final class PartialFunctionApplicationNodeHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof PartialFunctionApplicationNode;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$hasYield = false;
		$isAlwaysTerminating = false;
		$throwPoints = [];
		$impurePoints = [];
		$variableFlows = [];
		$dependencies = [];
		foreach ($this->getEvaluatedExprs($expr->getOriginalNode()) as $evaluatedExpr) {
			$result = $nodeScopeResolver->processExprNode($stmt, $evaluatedExpr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			$scope = $result->getScope();
			$hasYield = $hasYield || $result->hasYield();
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$variableFlows[] = $result->getVariableFlow();
			$dependencies[] = $result->getDependencies();
		}

		$result = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			variableFlow: VariableFlow::sequence(...$variableFlows),
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: static fn (bool $nativeTypesPromoted): Type => new ObjectType(Closure::class),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);

		return $result->withDependencies(Dependencies::merge(...$dependencies));
	}

	/**
	 * The callee's own expressions and the bound argument values, in the order
	 * PHP evaluates them.
	 *
	 * @return list<Expr>
	 */
	private function getEvaluatedExprs(Expr\CallLike $call): array
	{
		$exprs = [];
		if ($call instanceof Expr\FuncCall) {
			if ($call->name instanceof Expr) {
				$exprs[] = $call->name;
			}
		} elseif ($call instanceof Expr\MethodCall || $call instanceof Expr\NullsafeMethodCall) {
			$exprs[] = $call->var;
			if ($call->name instanceof Expr) {
				$exprs[] = $call->name;
			}
		} elseif ($call instanceof Expr\StaticCall) {
			if ($call->class instanceof Expr) {
				$exprs[] = $call->class;
			}
			if ($call->name instanceof Expr) {
				$exprs[] = $call->name;
			}
		} elseif ($call instanceof Expr\New_) {
			if ($call->class instanceof Expr) {
				$exprs[] = $call->class;
			}
		}

		foreach ($call->getRawArgs() as $arg) {
			if (!$arg instanceof Arg) {
				continue;
			}

			$exprs[] = $arg->value;
		}

		return $exprs;
	}

}
