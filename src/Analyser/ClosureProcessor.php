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
use PHPStan\Analyser\ExprHandler\Helper\ContextualClosureParameterResolver;
use PHPStan\Analyser\Generics\ClosureSignatureInference;
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
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ClosureType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function is_string;

/**
 * Walks closure and arrow function bodies for NodeScopeResolver and builds
 * the closure types from what the walk gathered (return statements, yields,
 * impure points, by-ref use convergence).
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ClosureProcessor.cpp')]
final class ClosureProcessor
{

	public function __construct(
		private Container $container,
		private ExpressionResultFactory $expressionResultFactory,
		private ClosureParameterResolver $closureParameterResolver,
		private ClosureTypeResolver $closureTypeResolver,
		private ContextualClosureParameterResolver $contextualClosureParameterResolver,
		private ClosureSignatureInference $closureSignatureInference,
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
		[$expectedReturnType, $nativeExpectedReturnType] = $this->contextualClosureParameterResolver->resolveExpectedReturnTypes($scope, $expr, $passedToType, $nativePassedToType);
		// the closure's own body may invoke it (use (&$self)): its sites must
		// exist before the body is observed
		if ($this->closureSignatureInference->isObserving($scope)) {
			$scope = $scope->addTemplateArgumentConstraints($this->closureSignatureInference->collectSites($scope, $this->closureTypeResolver->getClosureType($scope, $expr, true, $storage)));
		}

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
			if (is_string($use->var->name) && $scope->hasVariableType($use->var->name)->yes() && $this->closureSignatureInference->isObserving($scope)) {
				$scope = $scope->addTemplateArgumentConstraints(ClosureSignatureInference::collectCaptureEscapes($scope->getVariableType($use->var->name)));
			}
			if (!$use->byRef) {
				continue;
			}

			$useScope = $useScope->exitExpressionAssign($use->var);
		}

		if ($expr->returnType !== null) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $expr->returnType, $scope, $storage);
		}

		// the second pass of a body whose observation followed the closure: every
		// invocation seen (local) - the effects apply where it runs and the body
		// is analysed once all of them are known (see
		// processDeferredByRefClosureBody()); escaped - the fixpoint starts from
		// the states it was created in and invoked from. Both enter with the
		// creation state joined with the invocations' ones
		$byRefMode = count($byRefUses) > 0 ? $this->closureSignatureInference->getByRefSiteMode($scope, $expr) : null;
		$byRefEntrySource = $scope;
		if ($byRefMode !== null) {
			foreach ($byRefUses as $use) {
				if (!is_string($use->var->name)) {
					continue;
				}
				$seed = $this->closureSignatureInference->getByRefSeed($scope, $expr, $use->var->name);
				if ($seed === null) {
					continue;
				}
				$byRefEntrySource = $byRefEntrySource->assignVariable($use->var->name, $seed, $seed, TrinaryLogic::createYes());
			}
		}
		$bodyNodeCallback = $byRefMode === 'local' ? new NoopNodeCallback() : $nodeCallback;

		$closureScope = $scope->enterAnonymousFunction($expr, $callableParameters, $nativeCallableParameters);
		$closureScope = $closureScope->processClosureScope($byRefEntrySource, null, $byRefUses);
		$closureType = $closureScope->getAnonymousFunctionReflection();
		if (!$closureType instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$nodeScopeResolver->callNodeCallback($bodyNodeCallback, new InClosureNode($closureType, $expr), $closureScope, $storage);

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
				$statementResult = $nodeScopeResolver->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $nodeCallback, StatementContext::createTopLevel($context->shouldResolveTemplateArguments())->withExpectedReturnType($expectedReturnType, $nativeExpectedReturnType));
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
		// a local site's entry already holds every invocation's state: one walk
		// for the closure's own results (the rules and the inference of what is
		// inside run in the deferred one)
		while ($byRefMode !== 'local' && $count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS) {
			$prevScope = $closureScope;

			$storage = $originalStorage->duplicate();
			$bodyRecording = $bodyIsReplayable ? new RecordingNodeCallback() : new NoopNodeCallback();
			// deep context, like the loop handlers' own convergence passes: inner
			// loops walk single-pass here and only the final walk below (top-level)
			// runs their full convergence - otherwise every closure-convergence
			// pass would re-converge every inner loop from scratch
			$intermediaryClosureScopeResult = $nodeScopeResolver->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $bodyRecording, StatementContext::createDeep(resolveTemplateArguments: false)->withExpectedReturnType($expectedReturnType, $nativeExpectedReturnType));
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
		}

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
				$nodeScopeResolver->replayRecording($replayBodyRecording, $bodyNodeCallback, $originalStorage, $closureScope);
				$statementResult = $replayPassResult;
			} else {
				$statementResult = $nodeScopeResolver->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $bodyNodeCallback, StatementContext::createTopLevel($context->shouldResolveTemplateArguments())->withExpectedReturnType($expectedReturnType, $nativeExpectedReturnType));
			}
		} finally {
			$nodeScopeResolver->popNodeGatherer();
		}
		$publicStatementResult = $statementResult->toPublic();
		$closureReturnStatementsNodeScope = $this->refineClosureNodeScope($closureScope, $scope, $expr, $gatheredReturnStatementsWithScope, $gatheredYieldStatementsWithScope, $executionEnds, $statementResult->getThrowPoints(), array_merge($closureImpurePoints, $statementResult->getImpurePoints()), $invalidateExpressions, $storage);
		$nodeScopeResolver->callNodeCallback($bodyNodeCallback, new ClosureReturnStatementsNode(
			$expr,
			$gatheredReturnStatements,
			$gatheredReturnStatementsAfterFinally,
			$gatheredYieldStatements,
			$publicStatementResult,
			$executionEnds,
			array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
		), $closureReturnStatementsNodeScope, $storage);
		$nodeScopeResolver->callNodeCallback($bodyNodeCallback, VariableLivenessResolver::resolve($expr, $statementResult->getVariableFlow()), $closureReturnStatementsNodeScope, $storage);

		return new ProcessClosureResult(
			$scope->addTemplateArgumentConstraints($statementResult->getScope()->getTemplateArgumentConstraints()),
			$statementResult->getThrowPoints(),
			$statementResult->getImpurePoints(),
			$invalidateExpressions,
			$gatheredReturnStatementsWithScope,
			$gatheredYieldStatementsWithScope,
			$executionEnds,
			array_merge($closureImpurePoints, $statementResult->getImpurePoints()),
			// nothing runs at creation - an undefined by-ref variable is defined as null
			$byRefMode === 'local' ? $scope : $closureResultScope,
			$byRefUses,
		);
	}

	/**
	 * An invocation of a closure whose by-ref effects apply where it runs: its
	 * body is walked from the invocation's state - the by-ref variables as they
	 * are here, the other uses as they were captured at the closure's creation,
	 * the parameters as the arguments - once, or until the by-ref variables
	 * converge when the invocation may repeat unseen (the closure escaped, or
	 * the observation pass, which must contain every narrower second pass). The
	 * by-ref variables leave with the body's exit types.
	 *
	 * @param array<int, Type> $argumentTypes by position
	 * @return array{MutatingScope, list<InternalThrowPoint>}
	 */
	public function processByRefInvocation(
		NodeScopeResolver $nodeScopeResolver,
		Expr\Closure $expr,
		Expr $call,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		array $argumentTypes,
		MutatingScope $creationScope,
		bool $untilFixpoint,
	): array
	{
		$byRefUses = [];
		foreach ($expr->uses as $use) {
			if (!$use->byRef) {
				continue;
			}
			$byRefUses[] = $use;
		}
		$callableParameters = [];
		foreach ($expr->params as $i => $param) {
			if ($param->variadic || !$param->var instanceof Variable || !is_string($param->var->name)) {
				break;
			}
			$callableParameters[] = new DummyParameter($param->var->name, $argumentTypes[$i] ?? new MixedType(), false, null, false, null);
		}
		$enter = fn (MutatingScope $byRefSource): MutatingScope => $this->enterWithCapturedUses($scope, $expr, $callableParameters, $creationScope)->processClosureScope($byRefSource, null, $byRefUses);

		$entryScope = $enter($scope);
		$exitScope = null;
		$throwPoints = [];
		$count = 0;
		do {
			$result = $nodeScopeResolver->processStmtNodesInternal($expr, $expr->stmts, $entryScope, $storage->duplicate(), new NoopNodeCallback(), StatementContext::createTopLevel(false));
			$passExitScope = $result->getScope();
			foreach ($result->getExitPoints() as $exitPoint) {
				$passExitScope = $passExitScope->mergeWith($exitPoint->getScope());
			}
			$exitScope = $exitScope === null ? $passExitScope : $exitScope->mergeWith($passExitScope);
			foreach ($result->getThrowPoints() as $throwPoint) {
				$throwScope = $this->assignByRefUses($scope, $throwPoint->getScope(), $byRefUses);
				$throwPoints[] = $throwPoint->isExplicit()
					? InternalThrowPoint::createExplicit($throwScope, $throwPoint->getType(), $call, $throwPoint->canContainAnyThrowable())
					: InternalThrowPoint::createImplicit($throwScope, $call);
			}
			if (!$untilFixpoint) {
				break;
			}

			// the next run starts from any state a run may have left
			$nextEntryScope = $enter($this->assignByRefUses($scope, $entryScope, $byRefUses)->mergeWith($this->assignByRefUses($scope, $passExitScope, $byRefUses)));
			if ($nextEntryScope->equals($entryScope)) {
				break;
			}
			if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
				$nextEntryScope = $entryScope->generalizeWith($nextEntryScope);
			}
			$entryScope = $nextEntryScope;
			$count++;
		} while ($count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS);

		if ($untilFixpoint) {
			// zero or more runs: the state before them joins the ones after
			$exitScope = $exitScope->mergeWith($entryScope);
		}

		return [$this->assignByRefUses($scope, $exitScope, $byRefUses), $throwPoints];
	}

	/**
	 * The one walk of the body of a closure whose every invocation was seen,
	 * after the enclosing body's second pass, entered from the scope it was
	 * created in: the by-ref variables as the union of their types at the
	 * invocations (as at the creation when there was none). The rules inside
	 * the body are reported here.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 * @param array<string, Type> $byRefEntryTypes
	 */
	public function processDeferredByRefClosureBody(
		NodeScopeResolver $nodeScopeResolver,
		Expr\Closure $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		array $byRefEntryTypes,
	): void
	{
		$parameterTypes = $this->closureParameterResolver->resolve($scope, $expr, $storage, $expr->getAttribute(ClosureArgVisitor::ATTRIBUTE_NAME), null, null);
		[$expectedReturnType, $nativeExpectedReturnType] = $this->contextualClosureParameterResolver->resolveExpectedReturnTypes($scope, $expr, null, null);
		$byRefUses = [];
		$byRefSource = $scope;
		foreach ($expr->uses as $use) {
			if (!$use->byRef || !is_string($use->var->name)) {
				continue;
			}
			$byRefUses[] = $use;
			if (!isset($byRefEntryTypes[$use->var->name])) {
				continue;
			}
			$type = $byRefEntryTypes[$use->var->name];
			$byRefSource = $byRefSource->assignVariable($use->var->name, $type, $type, TrinaryLogic::createYes());
		}
		$closureScope = $scope->enterAnonymousFunction($expr, $parameterTypes->parameters, $parameterTypes->nativeParameters)->processClosureScope($byRefSource, null, $byRefUses);
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
		$closureStmtsGatherer = static function (Node $node, Scope $nodeScope) use (&$executionEnds, &$gatheredReturnStatements, &$gatheredReturnStatementsAfterFinally, &$gatheredReturnStatementsWithScope, &$gatheredYieldStatements, &$gatheredYieldStatementsWithScope, &$closureScope, &$closureImpurePoints, &$invalidateExpressions): void {
			if ($nodeScope->getAnonymousFunctionReflection() !== $closureScope->getAnonymousFunctionReflection()) {
				return;
			}
			if ($node instanceof PropertyAssignNode) {
				$closureImpurePoints[] = new ImpurePoint($nodeScope, $node, 'propertyAssign', 'property assignment', true);
				$invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
				return;
			}
			if ($node instanceof ExecutionEndNode) {
				$executionEnds[] = $node;
				return;
			}
			if ($node instanceof ReturnAfterFinallyNode) {
				$gatheredReturnStatementsAfterFinally[] = new ReturnStatement($nodeScope, $node->getReturnNode());
				return;
			}
			if ($node instanceof InvalidateExprNode) {
				$invalidateExpressions[] = $node;
				return;
			}
			if ($node instanceof Expr\Yield_ || $node instanceof Expr\YieldFrom) {
				$gatheredYieldStatements[] = $node;
				$gatheredYieldStatementsWithScope[] = [$node, $nodeScope];
			}
			if (!$node instanceof Return_) {
				return;
			}

			$gatheredReturnStatements[] = new ReturnStatement($nodeScope, $node);
			$gatheredReturnStatementsWithScope[] = [$node, $nodeScope];
		};

		$nodeScopeResolver->pushNodeGatherer($closureStmtsGatherer);
		try {
			$statementResult = $nodeScopeResolver->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $nodeCallback, StatementContext::createTopLevel(true)->withExpectedReturnType($expectedReturnType, $nativeExpectedReturnType));
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
	}

	/**
	 * The closure entered from $scope with its by-value uses as they were
	 * captured at its creation.
	 *
	 * @param ParameterReflection[] $callableParameters
	 */
	private function enterWithCapturedUses(MutatingScope $scope, Expr\Closure $expr, array $callableParameters, MutatingScope $creationScope): MutatingScope
	{
		$closureScope = $scope->enterAnonymousFunction($expr, $callableParameters, $callableParameters);
		foreach ($expr->uses as $use) {
			if ($use->byRef || !is_string($use->var->name)) {
				continue;
			}
			$type = $creationScope->hasVariableType($use->var->name)->yes() ? $creationScope->getVariableType($use->var->name) : new NullType();
			$closureScope = $closureScope->assignVariable($use->var->name, $type, $type, TrinaryLogic::createYes());
		}

		return $closureScope;
	}

	/**
	 * $scope with the by-ref variables as $source has them (null where it does
	 * not define them), invalidating what was known about their contents.
	 *
	 * @param Node\ClosureUse[] $byRefUses
	 */
	private function assignByRefUses(MutatingScope $scope, MutatingScope $source, array $byRefUses): MutatingScope
	{
		foreach ($byRefUses as $use) {
			if (!is_string($use->var->name)) {
				continue;
			}
			$type = $source->hasVariableType($use->var->name)->yes() ? $source->getVariableType($use->var->name) : new NullType();
			$scope = $scope->assignVariable($use->var->name, $type, $type, TrinaryLogic::createYes());
		}

		return $scope;
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
		if ($this->closureSignatureInference->isObserving($scope)) {
			foreach (ClosureSignatureInference::getArrowFunctionOuterVariables($expr) as $name) {
				if (!$scope->hasVariableType($name)->yes()) {
					continue;
				}
				$scope = $scope->addTemplateArgumentConstraints(ClosureSignatureInference::collectCaptureEscapes($scope->getVariableType($name)));
			}
		}
		if ($expr->returnType !== null) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $expr->returnType, $scope, $storage);
		}

		$arrowFunctionCallArgs = $expr->getAttribute(ArrowFunctionArgVisitor::ATTRIBUTE_NAME);
		$parameterTypes = $this->closureParameterResolver->resolve($scope, $expr, $storage, $arrowFunctionCallArgs, $passedToType, $nativePassedToType);
		$callableParameters = $parameterTypes->parameters;
		$nativeCallableParameters = $parameterTypes->nativeParameters;
		[$expectedReturnType, $nativeExpectedReturnType] = $this->contextualClosureParameterResolver->resolveExpectedReturnTypes($scope, $expr, $passedToType, $nativePassedToType);
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
			$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $arrowFunctionScope, $storage, $nodeCallback, ExpressionContext::createTopLevel($context->shouldResolveTemplateArguments())->enterPassedToType($expectedReturnType, $nativeExpectedReturnType));
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
