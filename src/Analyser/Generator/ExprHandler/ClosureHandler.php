<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generator\ExprHandler;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\Generator\ExprAnalysisRequest;
use PHPStan\Analyser\Generator\ExprAnalysisResult;
use PHPStan\Analyser\Generator\ExprHandler;
use PHPStan\Analyser\Generator\GeneratorScope;
use PHPStan\Analyser\Generator\NodeCallbackRequest;
use PHPStan\Analyser\Generator\StmtAnalysisResult;
use PHPStan\Analyser\Generator\StmtsAnalysisRequest;
use PHPStan\Analyser\Generator\TypeExprRequest;
use PHPStan\Analyser\Generator\TypeExprResult;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\StatementContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\InClosureNode;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;
use function array_merge;
use function count;
use function is_string;

/**
 * @implements ExprHandler<Closure>
 */
#[AutowiredService]
final class ClosureHandler implements ExprHandler
{

	public function __construct(
		private ClosureHelper $closureHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Closure;
	}

	public function analyseExpr(
		Stmt $stmt,
		Expr $expr,
		GeneratorScope $scope,
		ExpressionContext $context,
		?callable $alternativeNodeCallback,
	): Generator
	{
		$gen = $this->processClosureNode($stmt, $expr, $scope, $context, $alternativeNodeCallback);
		yield from $gen;
		[$result, $closureScope] = $gen->getReturn();

		if (!$result->type instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		yield new NodeCallbackRequest(new InClosureNode($result->type, $expr), $closureScope, $alternativeNodeCallback);

		return new ExprAnalysisResult(
			$result->type,
			$result->nativeType,
			$result->scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			specifiedTruthyTypes: new SpecifiedTypes(),
			specifiedFalseyTypes: new SpecifiedTypes(),
		);
	}

	/**
	 * @param (callable(Node, Scope, callable(Node, Scope): void): void)|null $alternativeNodeCallback
	 * @return Generator<int, ExprAnalysisRequest|TypeExprRequest|NodeCallbackRequest, ExprAnalysisResult
	 * |TypeExprResult, array{ExprAnalysisResult, GeneratorScope}>
	 */
	public function processClosureNode(
		Stmt $stmt,
		Closure $expr,
		GeneratorScope $scope,
		ExpressionContext $context,
		?callable $alternativeNodeCallback,
	): Generator
	{
		/*foreach ($expr->params as $param) {
			$this->processParamNode($stmt, $param, $scope, $nodeCallback);
		}*/

		$byRefUses = [];

		$callableParametersGen = $this->closureHelper->createCallableParameters($expr, $scope);
		yield from $callableParametersGen;
		$callableParameters = $callableParametersGen->getReturn();

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
					$inAssignRightSideExprTypeResult = yield new TypeExprRequest($inAssignRightSideExpr);
					$inAssignRightSideType = $inAssignRightSideExprTypeResult->type;
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
					$inAssignRightSideNativeType = $inAssignRightSideExprTypeResult->nativeType;
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
					$assignGen = $scope->assignVariable($inAssignRightSideVariableName, $variableType, $variableNativeType, TrinaryLogic::createYes());
					yield from $assignGen;
					$scope = $assignGen->getReturn();
				}
			}
			yield new ExprAnalysisRequest($stmt, $use->var, $useScope, $context, $alternativeNodeCallback);
			if (!$use->byRef) {
				continue;
			}

			$useScope = $useScope->exitExpressionAssign($use->var);
		}

		if ($expr->returnType !== null) {
			yield new NodeCallbackRequest($expr->returnType, $scope, $alternativeNodeCallback);
		}

		$closureScope = $scope->enterAnonymousFunctionWithoutReflection($expr, $callableParameters);
		$gatheredReturnStatements = [];
		$gatheredYieldStatements = [];
		$onlyNeverExecutionEnds = null;
		$closureImpurePoints = [];
		$invalidateExpressions = [];
		$executionEnds = [];

		$closureStmtsCallback = static function (Node $node, Scope $scope, callable $nodeCallback) use ($closureScope, &$gatheredReturnStatements, &$gatheredYieldStatements, &$onlyNeverExecutionEnds, &$closureImpurePoints, &$invalidateExpressions, &$executionEnds): void {
			$nodeCallback($node, $scope);
			if ($scope->getAnonymousFunctionReflection() !== $closureScope->getAnonymousFunctionReflection()) {
				return;
			}

			if ($node instanceof InvalidateExprNode) {
				$invalidateExpressions[] = $node;
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
				return;
			}

			if ($node instanceof ExecutionEndNode) {
				$executionEnds[] = $node;
				if ($node->getStatementResult()->isAlwaysTerminating()) {
					foreach ($node->getStatementResult()->getExitPoints() as $exitPoint) {
						if ($exitPoint->getStatement() instanceof Node\Stmt\Return_) {
							$onlyNeverExecutionEnds = false;
							continue;
						}

						if ($onlyNeverExecutionEnds === null) {
							$onlyNeverExecutionEnds = true;
						}

						break;
					}

					if (count($node->getStatementResult()->getExitPoints()) === 0) {
						if ($onlyNeverExecutionEnds === null) {
							$onlyNeverExecutionEnds = true;
						}
					}
				} else {
					$onlyNeverExecutionEnds = false;
				}

				return;
			}

			if ($node instanceof Node\Stmt\Return_) {
				$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
			}

			if (!$node instanceof Expr\Yield_ && !$node instanceof Expr\YieldFrom) {
				return;
			}

			$gatheredYieldStatements[] = $node;
		};

		if (count($byRefUses) === 0) {
			// @phpstan-ignore generator.valueType
			$closureStatementResult = yield new StmtsAnalysisRequest($expr->stmts, $closureScope, StatementContext::createTopLevel(), $closureStmtsCallback);
		} else {
			// todo
			$closureStatementResultGen = $this->processClosureNodeAndStabilizeScope(
				$expr,
				$closureScope,
				$closureStmtsCallback,
			);
			yield from $closureStatementResultGen;
			$closureStatementResult = $closureStatementResultGen->getReturn();
			$scope = $closureScope->processClosureScope($closureStatementResult->scope, $scope, $byRefUses);
		}

		$resultGen = $this->processClosureStatementResult(
			$expr,
			$closureStatementResult,
			$scope,
			$closureScope,
			$gatheredReturnStatements,
			$gatheredYieldStatements,
			$closureImpurePoints,
			$invalidateExpressions,
			$onlyNeverExecutionEnds,
		);
		yield from $resultGen;

		[$result, $closureScope] = $resultGen->getReturn();

		$publicStatementResult = (new StmtAnalysisResult(
			$closureScope,
			hasYield: $result->hasYield,
			isAlwaysTerminating: $result->isAlwaysTerminating,
			exitPoints: [],
			throwPoints: $result->throwPoints,
			impurePoints: $result->impurePoints,
		))->toPublic();

		yield new NodeCallbackRequest(
			new ClosureReturnStatementsNode(
				$expr,
				$gatheredReturnStatements,
				$gatheredYieldStatements,
				$publicStatementResult,
				$executionEnds,
				array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
			),
			$closureScope,
			$alternativeNodeCallback,
		);

		return $resultGen->getReturn();
	}

	/**
	 * @param ReturnStatement[] $closureReturnStatements
	 * @param array<Expr\Yield_|Expr\YieldFrom> $closureYieldStatements
	 * @param ImpurePoint[] $closureImpurePoints
	 * @param InvalidateExprNode[] $invalidateExpressions
	 */
	private function processClosureStatementResult(
		Closure $expr,
		StmtAnalysisResult $closureStatementResult,
		GeneratorScope $scope,
		GeneratorScope $closureScope,
		array $closureReturnStatements,
		array $closureYieldStatements,
		array $closureImpurePoints,
		array $invalidateExpressions,
		?bool $onlyNeverExecutionEnds,
	): Generator
	{
		$impurePoints = array_merge($closureImpurePoints, $closureStatementResult->impurePoints);
		$returnTypes = [];
		$hasNull = false;
		foreach ($closureReturnStatements as $returnNode) {
			$returnExpr = $returnNode->getReturnNode()->expr;
			if ($returnExpr === null) {
				$hasNull = true;
				continue;
			}

			$returnTypes[] = (yield new TypeExprRequest($returnExpr))->type;
		}

		if (count($returnTypes) === 0) {
			if ($onlyNeverExecutionEnds === true && !$hasNull) {
				$returnType = new NonAcceptingNeverType();
			} else {
				$returnType = new VoidType();
			}
		} else {
			if ($onlyNeverExecutionEnds === true) {
				$returnTypes[] = new NonAcceptingNeverType();
			}
			if ($hasNull) {
				$returnTypes[] = new NullType();
			}
			$returnType = TypeCombinator::union(...$returnTypes);
		}

		if (count($closureYieldStatements) > 0) {
			$keyTypes = [];
			$valueTypes = [];
			foreach ($closureYieldStatements as $yieldNode) {
				if ($yieldNode instanceof Expr\Yield_) {
					if ($yieldNode->key === null) {
						$keyTypes[] = new IntegerType();
					} else {
						$keyTypes[] = (yield new TypeExprRequest($yieldNode->key))->type;
					}

					if ($yieldNode->value === null) {
						$valueTypes[] = new NullType();
					} else {
						$keyTypes[] = (yield new TypeExprRequest($yieldNode->value))->type;
					}

					continue;
				}

				$yieldFromType = (yield new TypeExprRequest($yieldNode->expr))->type;
				$keyTypes[] = $closureScope->getIterableKeyType($yieldFromType);
				$valueTypes[] = $closureScope->getIterableValueType($yieldFromType);
			}

			$returnType = new GenericObjectType(Generator::class, [
				TypeCombinator::union(...$keyTypes),
				TypeCombinator::union(...$valueTypes),
				new MixedType(),
				$returnType,
			]);
		} else {
			if ($expr->returnType !== null) {
				$nativeReturnType = $closureScope->getFunctionType($expr->returnType, false, false);
				$returnType = GeneratorScope::intersectButNotNever($nativeReturnType, $returnType);
			}
		}

		$usedVariables = [];
		foreach ($expr->uses as $use) {
			if (!is_string($use->var->name)) {
				continue;
			}

			$usedVariables[] = $use->var->name;
		}

		foreach ($expr->uses as $use) {
			if (!$use->byRef) {
				continue;
			}

			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				'functionCall',
				'call to a Closure with by-ref use',
				true,
			);
			break;
		}

		$closureType = $this->closureHelper->createClosureType(
			$expr,
			$scope,
			$returnType,
			$closureStatementResult->throwPoints,
			$impurePoints,
			$invalidateExpressions,
			$usedVariables,
		);

		$closureScope = $closureScope->enterAnonymousFunction($closureType);

		return [
			new ExprAnalysisResult(
				$closureType,
				$closureType,
				$scope,
				hasYield: $closureStatementResult->hasYield,
				isAlwaysTerminating: $closureStatementResult->hasYield,
				throwPoints: $closureStatementResult->throwPoints,
				impurePoints: $closureStatementResult->impurePoints,
				specifiedTruthyTypes: new SpecifiedTypes(),
				specifiedFalseyTypes: new SpecifiedTypes(),
			),
			$closureScope,
		];
	}

	/**
	 * @param callable(Node, Scope, callable(Node, Scope): void): void $closureStmtsCallback
	 */
	private function processClosureNodeAndStabilizeScope(
		Closure $expr,
		GeneratorScope $closureScope,
		callable $closureStmtsCallback,
	): Generator
	{
		return yield new StmtsAnalysisRequest($expr->stmts, $closureScope, StatementContext::createTopLevel(), $closureStmtsCallback);
	}

}
