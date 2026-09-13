<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\ExprHandler\ArrowFunctionHandler;
use PHPStan\Analyser\ExprHandler\Helper\ClosureParameterResolver;
use PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Node\InClosureNode;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\ReturnAfterFinallyNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Parser\ArrowFunctionArgVisitor;
use PHPStan\Parser\ClosureArgVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function array_merge;
use function count;
use function in_array;

/**
 * Walks closure and arrow function bodies for NodeScopeResolver and builds
 * the closure types from what the walk gathered (return statements, yields,
 * impure points, by-ref use convergence).
 */
#[AutowiredService]
final class ClosureProcessor
{

	public function __construct(
		private Container $container,
		private ExpressionResultFactory $expressionResultFactory,
		private ClosureParameterResolver $closureParameterResolver,
		private ClosureTypeResolver $closureTypeResolver,
	)
	{
	}

	/**
	 * Looked up instead of injected: parameters walk their attributes, attribute
	 * arguments are call arguments walked by ArgumentsHandler, and those walk
	 * closure arguments through this class - a closure in an attribute argument
	 * recurses back here, which constructor injection cannot express.
	 */
	private function getParametersProcessor(): ParametersProcessor
	{
		return $this->container->getByType(ParametersProcessor::class);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processClosureNode(
		NodeScopeResolver $nodeScopeResolver,
		Node\Stmt $stmt,
		Expr\Closure $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
		?Type $passedToType,
		?Type $nativePassedToType = null,
	): ProcessClosureResult
	{
		return $this->processClosureNodeInternal($nodeScopeResolver, $stmt, $expr, $scope, $storage, $nodeCallback, $context, $passedToType, $nativePassedToType);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processClosureNodeInternal(
		NodeScopeResolver $nodeScopeResolver,
		Node\Stmt $stmt,
		Expr\Closure $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
		?Type $passedToType,
		?Type $nativePassedToType = null,
	): ProcessClosureResult
	{
		$this->getParametersProcessor()->processParams($nodeScopeResolver, $stmt, $expr->params, $scope, $storage, $nodeCallback);

		$byRefUses = [];

		$closureCallArgs = $expr->getAttribute(ClosureArgVisitor::ATTRIBUTE_NAME);
		$parameterTypes = $this->closureParameterResolver->resolve($scope, $expr, $storage, $closureCallArgs, $passedToType, $nativePassedToType);
		$callableParameters = $parameterTypes->parameters;
		$nativeCallableParameters = $parameterTypes->nativeParameters;

		$useScope = $scope;
		foreach ($expr->uses as $use) {
			if ($use->byRef) {
				$byRefUses[] = $use;
				$useScope = $useScope->enterExpressionAssign($use->var);

				$inAssignRightSideVariableName = $context->getInAssignRightSideVariableName();
				$inAssignRightSideExpr = $context->getInAssignRightSideExpr();
				if (
					$inAssignRightSideVariableName === $use->var->name
					&& $inAssignRightSideExpr !== null
				) {
					// a call's type is carried by the context (see
					// ExpressionContext::enterAssignRightSideCallArgs()); a closure
					// right side resolves through the closure type resolver
					$inAssignRightSideType = $context->getInAssignRightSideType() ?? $this->closureParameterResolver->resolveCallableTypeForScope($inAssignRightSideExpr, $scope);
					if ($inAssignRightSideType instanceof ClosureType) {
						$variableType = $inAssignRightSideType;
					} else {
						$alreadyHasVariableType = $scope->hasVariableType($inAssignRightSideVariableName);
						if ($alreadyHasVariableType->no()) {
							$variableType = TypeCombinator::union(new NullType(), $inAssignRightSideType);
						} else {
							$variableType = TypeCombinator::union($scope->getVariableType($inAssignRightSideVariableName), $inAssignRightSideType);
						}
					}
					$inAssignRightSideNativeType = $context->getInAssignRightSideNativeType() ?? $this->closureParameterResolver->resolveCallableTypeForScope($inAssignRightSideExpr, $scope->doNotTreatPhpDocTypesAsCertain());
					if ($inAssignRightSideNativeType instanceof ClosureType) {
						$variableNativeType = $inAssignRightSideNativeType;
					} else {
						$alreadyHasVariableType = $scope->hasVariableType($inAssignRightSideVariableName);
						if ($alreadyHasVariableType->no()) {
							$variableNativeType = TypeCombinator::union(new NullType(), $inAssignRightSideNativeType);
						} else {
							$variableNativeType = TypeCombinator::union($scope->getVariableType($inAssignRightSideVariableName), $inAssignRightSideNativeType);
						}
					}
					$scope = $scope->assignVariable($inAssignRightSideVariableName, $variableType, $variableNativeType, TrinaryLogic::createYes());
				}
			}
			$nodeScopeResolver->processExprNode($stmt, $use->var, $useScope, $storage, $nodeCallback, $context->withoutValueFlow());
			if (!$use->byRef) {
				continue;
			}

			$useScope = $useScope->exitExpressionAssign($use->var);
		}

		if ($expr->returnType !== null) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $expr->returnType, $scope, $storage);
		}

		$closureScope = $scope->enterAnonymousFunction($expr, $callableParameters, $nativeCallableParameters);
		$closureScope = $closureScope->processClosureScope($scope, null, $byRefUses);
		$closureType = $closureScope->getAnonymousFunctionReflection();
		if (!$closureType instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, new InClosureNode($closureType, $expr), $closureScope, $storage);

		$executionEnds = [];
		$gatheredReturnStatements = [];
		$gatheredReturnStatementsAfterFinally = [];
		$gatheredReturnStatementsWithScope = [];
		$gatheredYieldStatements = [];
		$gatheredYieldStatementsWithScope = [];
		$closureImpurePoints = [];
		$invalidateExpressions = [];
		$closureStmtsGatherer = static function (Node $node, Scope $scope) use (&$executionEnds, &$gatheredReturnStatements, &$gatheredReturnStatementsAfterFinally, &$gatheredReturnStatementsWithScope, &$gatheredYieldStatements, &$gatheredYieldStatementsWithScope, &$closureScope, &$closureImpurePoints, &$invalidateExpressions): void {
			if ($scope->getAnonymousFunctionReflection() !== $closureScope->getAnonymousFunctionReflection()) {
				return;
			}
			if ($node instanceof PropertyAssignNode) {
				$closureImpurePoints[] = new ImpurePoint(
					$scope,
					$node,
					'propertyAssign',
					'property assignment',
					true,
				);
				$invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
				return;
			}
			if ($node instanceof ExecutionEndNode) {
				$executionEnds[] = $node;
				return;
			}
			if ($node instanceof ReturnAfterFinallyNode) {
				$gatheredReturnStatementsAfterFinally[] = new ReturnStatement($scope, $node->getReturnNode());
				return;
			}
			if ($node instanceof InvalidateExprNode) {
				$invalidateExpressions[] = $node;
				return;
			}
			if ($node instanceof Expr\Yield_ || $node instanceof Expr\YieldFrom) {
				$gatheredYieldStatements[] = $node;
				$gatheredYieldStatementsWithScope[] = [$node, $scope];
			}
			if (!$node instanceof Return_) {
				return;
			}

			$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
			$gatheredReturnStatementsWithScope[] = [$node, $scope];
		};

		if (count($byRefUses) === 0) {
			$nodeScopeResolver->pushNodeGatherer($closureStmtsGatherer);
			try {
				$statementResult = $nodeScopeResolver->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $nodeCallback, StatementContext::createTopLevel($context->shouldResolveTemplateArguments()));
			} finally {
				$nodeScopeResolver->popNodeGatherer();
			}
			$publicStatementResult = $statementResult->toPublic();
			$closureReturnStatementsNodeScope = $this->refineClosureNodeScope($closureScope, $scope, $expr, $gatheredReturnStatementsWithScope, $gatheredYieldStatementsWithScope, $executionEnds, $statementResult->getThrowPoints(), array_merge($closureImpurePoints, $statementResult->getImpurePoints()), $invalidateExpressions, $storage);
			$nodeScopeResolver->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
				$expr,
				$gatheredReturnStatements,
				$gatheredReturnStatementsAfterFinally,
				$gatheredYieldStatements,
				$publicStatementResult,
				$executionEnds,
				array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
			), $closureReturnStatementsNodeScope, $storage);
			$nodeScopeResolver->callNodeCallback($nodeCallback, VariableLivenessResolver::resolve($expr, $statementResult->getVariableFlow()), $closureReturnStatementsNodeScope, $storage);

			return new ProcessClosureResult(
				$scope->addTemplateArgumentConstraints($statementResult->getScope()->getTemplateArgumentConstraints()),
				$statementResult->getThrowPoints(),
				$statementResult->getImpurePoints(),
				$invalidateExpressions,
				$gatheredReturnStatementsWithScope,
				$gatheredYieldStatementsWithScope,
				$executionEnds,
				array_merge($closureImpurePoints, $statementResult->getImpurePoints()),
			);
		}

		$originalStorage = $storage;

		$count = 0;
		$closureResultScope = null;
		$replayBodyRecording = null;
		$replayPassStorage = null;
		$replayPassResult = null;
		$replayEntryScope = null;
		$bodyIsReplayable = $nodeScopeResolver->isReplayableConvergenceBody($expr, $expr->stmts);
		do {
			$prevScope = $closureScope;

			$storage = $originalStorage->duplicate();
			$bodyRecording = $bodyIsReplayable ? new RecordingNodeCallback() : new NoopNodeCallback();
			// deep context, like the loop handlers' own convergence passes: inner
			// loops walk single-pass here and only the final walk below (top-level)
			// runs their full convergence - otherwise every closure-convergence
			// pass would re-converge every inner loop from scratch
			$intermediaryClosureScopeResult = $nodeScopeResolver->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $bodyRecording, StatementContext::createDeep(resolveTemplateArguments: false));
			// the candidate to replace the final walk when this pass's entry
			// turns out to be the fixpoint
			if ($bodyRecording instanceof RecordingNodeCallback) {
				$replayBodyRecording = $bodyRecording;
				$replayPassStorage = $storage;
				$replayPassResult = $intermediaryClosureScopeResult;
				$replayEntryScope = $prevScope;
			}
			$intermediaryClosureScope = $intermediaryClosureScopeResult->getScope();
			foreach ($intermediaryClosureScopeResult->getExitPoints() as $exitPoint) {
				$intermediaryClosureScope = $intermediaryClosureScope->mergeWith($exitPoint->getScope());
			}

			if ($expr->getAttribute(ImmediatelyInvokedClosureVisitor::ATTRIBUTE_NAME) === true) {
				$closureResultScope = $intermediaryClosureScope;
				break;
			}

			$closureScope = $scope->enterAnonymousFunction($expr, $callableParameters, $nativeCallableParameters);
			$closureScope = $closureScope->processClosureScope($intermediaryClosureScope, $prevScope, $byRefUses);

			if ($closureScope->equals($prevScope)) {
				break;
			}
			if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
				$closureScope = $prevScope->generalizeWith($closureScope);
			}
			$count++;
		} while ($count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS);

		if ($closureResultScope === null) {
			$closureResultScope = $closureScope;
		}

		$storage = $originalStorage;
		$nodeScopeResolver->pushNodeGatherer($closureStmtsGatherer);
		try {
			if (
				$replayBodyRecording !== null && $replayPassStorage !== null
				&& $replayPassResult !== null && $replayEntryScope !== null
				&& $closureScope->equals($replayEntryScope)
			) {
				// the final walk would repeat the recorded fixpoint pass exactly
				// (same entry scope, deterministic walk) - adopt the pass's result
				// and replay its emissions through the real callback instead.
				// The pass's own entry scope takes over: the recorded pairs carry
				// its anonymous-function reflection, which the gatherer's filter
				// compares by identity (the state is equals-identical anyway).
				$closureScope = $replayEntryScope;
				$originalStorage->mergeResults($replayPassStorage);
				$nodeScopeResolver->replayRecording($replayBodyRecording, $nodeCallback, $originalStorage, $closureScope);
				$statementResult = $replayPassResult;
			} else {
				$statementResult = $nodeScopeResolver->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $nodeCallback, StatementContext::createTopLevel($context->shouldResolveTemplateArguments()));
			}
		} finally {
			$nodeScopeResolver->popNodeGatherer();
		}
		$publicStatementResult = $statementResult->toPublic();
		$closureReturnStatementsNodeScope = $this->refineClosureNodeScope($closureScope, $scope, $expr, $gatheredReturnStatementsWithScope, $gatheredYieldStatementsWithScope, $executionEnds, $statementResult->getThrowPoints(), array_merge($closureImpurePoints, $statementResult->getImpurePoints()), $invalidateExpressions, $storage);
		$nodeScopeResolver->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
			$expr,
			$gatheredReturnStatements,
			$gatheredReturnStatementsAfterFinally,
			$gatheredYieldStatements,
			$publicStatementResult,
			$executionEnds,
			array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
		), $closureReturnStatementsNodeScope, $storage);
		$nodeScopeResolver->callNodeCallback($nodeCallback, VariableLivenessResolver::resolve($expr, $statementResult->getVariableFlow()), $closureReturnStatementsNodeScope, $storage);

		return new ProcessClosureResult(
			$scope->addTemplateArgumentConstraints($statementResult->getScope()->getTemplateArgumentConstraints()),
			$statementResult->getThrowPoints(),
			$statementResult->getImpurePoints(),
			$invalidateExpressions,
			$gatheredReturnStatementsWithScope,
			$gatheredYieldStatementsWithScope,
			$executionEnds,
			array_merge($closureImpurePoints, $statementResult->getImpurePoints()),
			$closureResultScope,
			$byRefUses,
		);
	}

	/**
	 * The closure scope was entered with a shallow reflection (parameters +
	 * declared return, no body walk - see ClosureTypeResolver::getClosureType()
	 * with $shallow). Now that the single body walk has gathered the returns,
	 * build the refined ClosureType from them (no second walk) and swap it onto
	 * the scope the ClosureReturnStatementsNode fires with, so the return-type
	 * rules see the refined expected return (e.g. Bar&Foo, not just Foo).
	 *
	 * @param list<array{Return_, Scope}> $gatheredReturnStatementsWithScope
	 * @param list<array{Expr\Yield_|Expr\YieldFrom, Scope}> $gatheredYieldStatementsWithScope
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param InvalidateExprNode[] $invalidateExpressions
	 */
	private function refineClosureNodeScope(
		MutatingScope $closureScope,
		MutatingScope $scope,
		Expr\Closure $expr,
		array $gatheredReturnStatementsWithScope,
		array $gatheredYieldStatementsWithScope,
		array $executionEnds,
		array $throwPoints,
		array $impurePoints,
		array $invalidateExpressions,
		ExpressionResultStorage $storage,
	): MutatingScope
	{
		$refinedClosureType = $this->closureTypeResolver->buildClosureTypeForClosure(
			$scope,
			$expr,
			$gatheredReturnStatementsWithScope,
			$gatheredYieldStatementsWithScope,
			$executionEnds,
			$throwPoints,
			$impurePoints,
			$invalidateExpressions,
			false,
			$storage,
		);

		return $closureScope->withAnonymousFunctionReflection($refinedClosureType);
	}

	/**
	 * @param InvalidateExprNode[] $invalidatedExpressions
	 * @param string[] $uses
	 */
	public function processImmediatelyCalledCallable(MutatingScope $scope, array $invalidatedExpressions, array $uses): MutatingScope
	{
		if ($scope->isInClass()) {
			$uses[] = 'this';
		}

		$finder = new NodeFinder();
		foreach ($invalidatedExpressions as $invalidateExpression) {
			$result = $finder->findFirst([$invalidateExpression->getExpr()], static fn ($node) => $node instanceof Variable && in_array($node->name, $uses, true));
			if ($result === null) {
				continue;
			}

			$requireMoreCharacters = $invalidateExpression->getExpr() instanceof Variable;
			$scope = $scope->invalidateExpression($invalidateExpression->getExpr(), $requireMoreCharacters);
		}

		return $scope;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processArrowFunctionNode(
		NodeScopeResolver $nodeScopeResolver,
		Node\Stmt $stmt,
		Expr\ArrowFunction $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		?Type $passedToType,
		?Type $nativePassedToType = null,
		?ExpressionContext $context = null,
	): ProcessArrowFunctionResult
	{
		$context ??= ExpressionContext::createTopLevel();
		$this->getParametersProcessor()->processParams($nodeScopeResolver, $stmt, $expr->params, $scope, $storage, $nodeCallback);
		if ($expr->returnType !== null) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $expr->returnType, $scope, $storage);
		}

		$arrowFunctionCallArgs = $expr->getAttribute(ArrowFunctionArgVisitor::ATTRIBUTE_NAME);
		$parameterTypes = $this->closureParameterResolver->resolve($scope, $expr, $storage, $arrowFunctionCallArgs, $passedToType, $nativePassedToType);
		$callableParameters = $parameterTypes->parameters;
		$nativeCallableParameters = $parameterTypes->nativeParameters;
		$arrowFunctionScope = $scope->enterArrowFunction($expr, $callableParameters, $nativeCallableParameters);
		if ($arrowFunctionScope->getAnonymousFunctionReflection() === null) {
			throw new ShouldNotHappenException();
		}

		// Gather the property-assign impure points and invalidate expressions the
		// arrow function type needs (mirroring ClosureTypeResolver::getClosureType()),
		// on top of the regular rule node callback, so the single body walk here
		// feeds ClosureTypeResolver::buildClosureTypeForArrowFunction().
		$arrowFunctionImpurePoints = [];
		$invalidateExpressions = [];
		$arrowFunctionStmtsGatherer = static function (Node $node, Scope $innerScope) use ($arrowFunctionScope, &$arrowFunctionImpurePoints, &$invalidateExpressions): void {
			if ($innerScope->getAnonymousFunctionReflection() !== $arrowFunctionScope->getAnonymousFunctionReflection()) {
				return;
			}

			if ($node instanceof InvalidateExprNode) {
				$invalidateExpressions[] = $node;
				return;
			}

			if (!$node instanceof PropertyAssignNode) {
				return;
			}

			$arrowFunctionImpurePoints[] = new ImpurePoint(
				$innerScope,
				$node,
				'propertyAssign',
				'property assignment',
				true,
			);
			$invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
		};

		$nodeScopeResolver->pushNodeGatherer($arrowFunctionStmtsGatherer);
		try {
			$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $arrowFunctionScope, $storage, $nodeCallback, ExpressionContext::createTopLevel($context->shouldResolveTemplateArguments()));
		} finally {
			$nodeScopeResolver->popNodeGatherer();
		}
		$scope = $scope->addTemplateArgumentConstraints($exprResult->getScope()->getTemplateArgumentConstraints());
		$scope = $scope->addTemplateArgumentConstraints($nodeScopeResolver->collectReturnSend($arrowFunctionScope, $exprResult));

		$closureTypeThrowPoints = array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->toPublic(), $exprResult->getThrowPoints());
		$closureTypeImpurePoints = array_merge($arrowFunctionImpurePoints, $exprResult->getImpurePoints());

		// The arrow scope was entered with a shallow reflection (parameters +
		// declared return, no body walk). Now that the single body walk above has
		// run, build the refined arrow function type from the body expression's
		// stored type (no second walk) and fire InArrowFunctionNode with it, so the
		// node and the return-type rules see the refined expected return.
		$refinedArrowFunctionType = $this->closureTypeResolver->buildClosureTypeForArrowFunction(
			$scope,
			$expr,
			$arrowFunctionScope,
			$closureTypeThrowPoints,
			$closureTypeImpurePoints,
			$invalidateExpressions,
			false,
			$storage,
		);
		$refinedArrowFunctionScope = $arrowFunctionScope->withAnonymousFunctionReflection($refinedArrowFunctionType);
		$nodeScopeResolver->callNodeCallback($nodeCallback, new InArrowFunctionNode($refinedArrowFunctionType, $expr), $refinedArrowFunctionScope, $storage);

		return new ProcessArrowFunctionResult(
			$this->expressionResultFactory->create(
				$scope,
				beforeScope: $scope,
				expr: $expr,
				hasYield: false,
				isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
				variableFlow: ArrowFunctionHandler::getVariableFlow($expr, $exprResult),
				throwPoints: $exprResult->getThrowPoints(),
				impurePoints: $exprResult->getImpurePoints(),
				typeCallback: static fn () => new MixedType(),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			),
			$arrowFunctionScope,
			$closureTypeThrowPoints,
			$closureTypeImpurePoints,
			$invalidateExpressions,
		);
	}

}
