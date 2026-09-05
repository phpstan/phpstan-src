<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\DeprecatedAttributeResolver;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\PhpDocsResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\ShouldNotHappenException;
use function array_merge;

/**
 * @implements StmtHandler<Function_>
 */
#[AutowiredService]
final class FunctionHandler implements StmtHandler
{

	public function __construct(
		private DeprecatedAttributeResolver $deprecatedAttributeResolver,
		private PhpDocsResolver $phpDocsResolver,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Function_;
	}

	public function processStmt(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$nodeScopeResolver->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);
		[$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, , $isPure, $acceptsNamedArguments, , $phpDocComment, $asserts,, $phpDocParameterOutTypes, , , , $pureUnlessCallableIsImpureParameters, $pureUnlessParameterPassedParameters] = $this->phpDocsResolver->getPhpDocs($scope, $stmt);

		foreach ($stmt->params as $param) {
			$nodeScopeResolver->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
		}

		if ($stmt->returnType !== null) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt->returnType, $scope, $storage);
		}

		if (!$isDeprecated) {
			[$isDeprecated, $deprecatedDescription] = $this->deprecatedAttributeResolver->getDeprecatedAttribute($scope, $stmt);
		}

		$functionScope = $scope->enterFunction(
			$stmt,
			$templateTypeMap,
			$phpDocParameterTypes,
			$phpDocReturnType,
			$phpDocThrowType,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$isPure,
			$acceptsNamedArguments,
			$asserts,
			$phpDocComment,
			$phpDocParameterOutTypes,
			$phpDocImmediatelyInvokedCallableParameters,
			$phpDocClosureThisTypeParameters,
			$pureUnlessCallableIsImpureParameters,
			$pureUnlessParameterPassedParameters,
		);
		$functionReflection = $functionScope->getFunction();
		if (!$functionReflection instanceof PhpFunctionFromParserNodeReflection) {
			throw new ShouldNotHappenException();
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, new InFunctionNode($functionReflection, $stmt), $functionScope, $storage);

		$gatheredReturnStatements = [];
		$gatheredYieldStatements = [];
		$executionEnds = [];
		$functionImpurePoints = [];
			$nodeScopeResolver->pushNodeGatherer(static function (Node $node, Scope $scope) use ($functionScope, &$gatheredReturnStatements, &$gatheredYieldStatements, &$executionEnds, &$functionImpurePoints): void {
				if ($scope->getFunction() !== $functionScope->getFunction()) {
					return;
				}
				if ($scope->isInAnonymousFunction()) {
					return;
				}
				if ($node instanceof PropertyAssignNode) {
					$functionImpurePoints[] = new ImpurePoint(
						$scope,
						$node,
						'propertyAssign',
						'property assignment',
						true,
					);
					return;
				}
				if ($node instanceof ExecutionEndNode) {
					$executionEnds[] = $node;
					return;
				}
				if ($node instanceof Expr\Yield_ || $node instanceof Expr\YieldFrom) {
					$gatheredYieldStatements[] = $node;
				}
				if (!$node instanceof Return_) {
					return;
				}

				$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
			});
		try {
			$statementResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $functionScope, $storage, $nodeCallback, StatementContext::createTopLevel())->toPublic();
		} finally {
			$nodeScopeResolver->popNodeGatherer();
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, new FunctionReturnStatementsNode(
			$stmt,
			$gatheredReturnStatements,
			$gatheredYieldStatements,
			$statementResult,
			$executionEnds,
			array_merge($statementResult->getImpurePoints(), $functionImpurePoints),
			$functionReflection,
		), $functionScope, $storage);

		// declaring the function defines it in global state, so a negative
		// function_exists() narrowing that may refer to that function must be forgotten
		$scope = $scope->invalidateExistenceCheckExpressions(['function_exists'], $functionReflection->getName());

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
	}

}
