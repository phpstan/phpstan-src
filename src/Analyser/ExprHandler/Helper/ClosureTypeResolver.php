<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function is_string;

#[AutowiredService]
final class ClosureTypeResolver
{

	private static int $resolveClosureTypeDepth = 0;

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
	)
	{
	}

	public function getClosureType(
		MutatingScope $scope,
		Node\Expr\Closure|ArrowFunction $expr,
	): ClosureType
	{
		$parameters = [];
		$isVariadic = false;
		$firstOptionalParameterIndex = null;
		foreach ($expr->params as $i => $param) {
			$isOptionalCandidate = $param->default !== null || $param->variadic;

			if ($isOptionalCandidate) {
				if ($firstOptionalParameterIndex === null) {
					$firstOptionalParameterIndex = $i;
				}
			} else {
				$firstOptionalParameterIndex = null;
			}
		}

		foreach ($expr->params as $i => $param) {
			if ($param->variadic) {
				$isVariadic = true;
			}
			if (!$param->var instanceof Variable || !is_string($param->var->name)) {
				throw new ShouldNotHappenException();
			}
			$parameters[] = new NativeParameterReflection(
				$param->var->name,
				$firstOptionalParameterIndex !== null && $i >= $firstOptionalParameterIndex,
				$scope->getFunctionType($param->type, $scope->isParameterValueNullable($param), false),
				$param->byRef
					? PassedByReference::createCreatesNewVariable()
					: PassedByReference::createNo(),
				$param->variadic,
				$param->default !== null ? $scope->getType($param->default) : null,
			);
		}

		$callableParameters = null;
		$nativeCallableParameters = null;
		$arrayMapArgs = $expr->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME);
		$immediatelyInvokedArgs = $expr->getAttribute(ImmediatelyInvokedClosureVisitor::ARGS_ATTRIBUTE_NAME);
		if ($arrayMapArgs !== null) {
			$callableParameters = [];
			$nativeCallableParameters = [];
			foreach ($arrayMapArgs as $funcCallArg) {
				$callableParameters[] = new DummyParameter('item', $scope->getType($funcCallArg->value)->getIterableValueType(), optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
				$nativeCallableParameters[] = new DummyParameter('item', $scope->getNativeType($funcCallArg->value)->getIterableValueType(), optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
			}
		} elseif ($immediatelyInvokedArgs !== null) {
			foreach ($immediatelyInvokedArgs as $immediatelyInvokedArg) {
				$callableParameters[] = new DummyParameter('item', $scope->getType($immediatelyInvokedArg->value), optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
				$nativeCallableParameters[] = new DummyParameter('item', $scope->getNativeType($immediatelyInvokedArg->value), optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
			}
		} else {
			$inFunctionCallsStackCount = count($scope->inFunctionCallsStack);
			if ($inFunctionCallsStackCount > 0) {
				[, $inParameter] = $scope->inFunctionCallsStack[$inFunctionCallsStackCount - 1];
				if ($inParameter !== null) {
					$callableParameters = $this->nodeScopeResolver->createCallableParameters($scope, $expr, null, $inParameter->getType());
					$nativeType = $inParameter instanceof ExtendedParameterReflection ? $inParameter->getNativeType() : $inParameter->getType();
					$nativeCallableParameters = $this->nodeScopeResolver->createNativeCallableParameters($scope, $expr, null, $nativeType);
				}
			}
		}

		if ($expr instanceof ArrowFunction) {
			$arrowScope = $scope->enterArrowFunctionWithoutReflection($expr, $callableParameters, $nativeCallableParameters);

			if ($expr->expr instanceof Yield_ || $expr->expr instanceof YieldFrom) {
				$yieldNode = $expr->expr;

				if ($yieldNode instanceof Yield_) {
					if ($yieldNode->key === null) {
						$keyType = new IntegerType();
					} else {
						$keyType = $arrowScope->getType($yieldNode->key);
					}

					if ($yieldNode->value === null) {
						$valueType = new NullType();
					} else {
						$valueType = $arrowScope->getType($yieldNode->value);
					}
				} else {
					$yieldFromType = $arrowScope->getType($yieldNode->expr);
					$keyType = $arrowScope->getIterableKeyType($yieldFromType);
					$valueType = $arrowScope->getIterableValueType($yieldFromType);
				}

				$returnType = new GenericObjectType(Generator::class, [
					$keyType,
					$valueType,
					new MixedType(),
					new VoidType(),
				]);
			} else {
				$returnType = $arrowScope->getKeepVoidType($expr->expr);
				if ($expr->returnType !== null) {
					$nativeReturnType = $scope->getFunctionType($expr->returnType, false, false);
					$returnType = MutatingScope::intersectButNotNever($nativeReturnType, $returnType);
				}
			}

			$arrowFunctionImpurePoints = [];
			$invalidateExpressions = [];
			$arrowFunctionExprResult = $this->nodeScopeResolver->processExprNode(
				new Node\Stmt\Expression($expr->expr),
				$expr->expr,
				$arrowScope,
				new ExpressionResultStorage(),
				static function (Node $node, Scope $scope) use ($arrowScope, &$arrowFunctionImpurePoints, &$invalidateExpressions): void {
					if ($scope->getAnonymousFunctionReflection() !== $arrowScope->getAnonymousFunctionReflection()) {
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
						$scope,
						$node,
						'propertyAssign',
						'property assignment',
						true,
					);
					$invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
				},
				ExpressionContext::createDeep(),
			);
			$throwPoints = array_map(static fn ($throwPoint) => $throwPoint->toPublic(), $arrowFunctionExprResult->getThrowPoints());
			$impurePoints = array_merge($arrowFunctionImpurePoints, $arrowFunctionExprResult->getImpurePoints());
			$usedVariables = [];
		} else {
			$cachedTypes = $expr->getAttribute('phpstanCachedTypes', []);
			$cacheKey = $scope->getClosureScopeCacheKey();
			if (array_key_exists($cacheKey, $cachedTypes)) {
				$cachedClosureData = $cachedTypes[$cacheKey];

				$mustUseReturnValue = TrinaryLogic::createNo();
				foreach ($expr->attrGroups as $attrGroup) {
					foreach ($attrGroup->attrs as $attr) {
						if ($attr->name->toLowerString() === 'nodiscard') {
							$mustUseReturnValue = TrinaryLogic::createYes();
							break;
						}
					}
				}

				return new ClosureType(
					$parameters,
					$cachedClosureData['returnType'],
					$isVariadic,
					TemplateTypeMap::createEmpty(),
					TemplateTypeMap::createEmpty(),
					TemplateTypeVarianceMap::createEmpty(),
					throwPoints: $cachedClosureData['throwPoints'],
					impurePoints: $cachedClosureData['impurePoints'],
					invalidateExpressions: $cachedClosureData['invalidateExpressions'],
					usedVariables: $cachedClosureData['usedVariables'],
					acceptsNamedArguments: TrinaryLogic::createYes(),
					mustUseReturnValue: $mustUseReturnValue,
				);
			}
			if (self::$resolveClosureTypeDepth >= 2) {
				return new ClosureType(
					$parameters,
					$scope->getFunctionType($expr->returnType, false, false),
					$isVariadic,
				);
			}

			self::$resolveClosureTypeDepth++;

			$closureScope = $scope->enterAnonymousFunctionWithoutReflection($expr, $callableParameters, $nativeCallableParameters);
			$closureReturnStatements = [];
			$closureYieldStatements = [];
			$onlyNeverExecutionEnds = null;
			$closureImpurePoints = [];
			$invalidateExpressions = [];

			try {
				$closureStatementResult = $this->nodeScopeResolver->processStmtNodes($expr, $expr->stmts, $closureScope, static function (Node $node, Scope $scope) use ($closureScope, &$closureReturnStatements, &$closureYieldStatements, &$onlyNeverExecutionEnds, &$closureImpurePoints, &$invalidateExpressions): void {
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
						$invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
						return;
					}

					if ($node instanceof ExecutionEndNode) {
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
						$closureReturnStatements[] = [$node, $scope];
					}

					if (!$node instanceof Yield_ && !$node instanceof YieldFrom) {
						return;
					}

					$closureYieldStatements[] = [$node, $scope];
				}, StatementContext::createTopLevel());
			} finally {
				self::$resolveClosureTypeDepth--;
			}

			$throwPoints = $closureStatementResult->getThrowPoints();
			$impurePoints = array_merge($closureImpurePoints, $closureStatementResult->getImpurePoints());

			$returnTypes = [];
			$hasNull = false;
			foreach ($closureReturnStatements as [$returnNode, $returnScope]) {
				if ($returnNode->expr === null) {
					$hasNull = true;
					continue;
				}

				$returnTypes[] = $returnScope->toMutatingScope()->getType($returnNode->expr);
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
				foreach ($closureYieldStatements as [$yieldNode, $yieldScope]) {
					if ($yieldNode instanceof Yield_) {
						if ($yieldNode->key === null) {
							$keyTypes[] = new IntegerType();
						} else {
							$keyTypes[] = $yieldScope->toMutatingScope()->getType($yieldNode->key);
						}

						if ($yieldNode->value === null) {
							$valueTypes[] = new NullType();
						} else {
							$valueTypes[] = $yieldScope->toMutatingScope()->getType($yieldNode->value);
						}

						continue;
					}

					$yieldFromType = $yieldScope->toMutatingScope()->getType($yieldNode->expr);
					$keyTypes[] = $yieldScope->toMutatingScope()->getIterableKeyType($yieldFromType);
					$valueTypes[] = $yieldScope->toMutatingScope()->getIterableValueType($yieldFromType);
				}

				$returnType = new GenericObjectType(Generator::class, [
					TypeCombinator::union(...$keyTypes),
					TypeCombinator::union(...$valueTypes),
					new MixedType(),
					$returnType,
				]);
			} else {
				if ($expr->returnType !== null) {
					$nativeReturnType = $scope->getFunctionType($expr->returnType, false, false);
					$returnType = MutatingScope::intersectButNotNever($nativeReturnType, $returnType);
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
		}

		foreach ($parameters as $parameter) {
			if ($parameter->passedByReference()->no()) {
				continue;
			}

			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				'functionCall',
				'call to a Closure with by-ref parameter',
				true,
			);
		}

		$throwPointsForClosureType = array_map(static fn (ThrowPoint $throwPoint) => $throwPoint->isExplicit() ? SimpleThrowPoint::createExplicit($throwPoint->getType(), $throwPoint->canContainAnyThrowable()) : SimpleThrowPoint::createImplicit(), $throwPoints);
		$impurePointsForClosureType = array_map(static fn (ImpurePoint $impurePoint) => new SimpleImpurePoint($impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain()), $impurePoints);

		$cachedTypes = $expr->getAttribute('phpstanCachedTypes', []);
		$cachedTypes[$scope->getClosureScopeCacheKey()] = [
			'returnType' => $returnType,
			'throwPoints' => $throwPointsForClosureType,
			'impurePoints' => $impurePointsForClosureType,
			'invalidateExpressions' => $invalidateExpressions,
			'usedVariables' => $usedVariables,
		];
		$expr->setAttribute('phpstanCachedTypes', $cachedTypes);

		$mustUseReturnValue = TrinaryLogic::createNo();
		foreach ($expr->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toLowerString() === 'nodiscard') {
					$mustUseReturnValue = TrinaryLogic::createYes();
					break;
				}
			}
		}

		return new ClosureType(
			$parameters,
			$returnType,
			$isVariadic,
			TemplateTypeMap::createEmpty(),
			TemplateTypeMap::createEmpty(),
			TemplateTypeVarianceMap::createEmpty(),
			throwPoints: $throwPointsForClosureType,
			impurePoints: $impurePointsForClosureType,
			invalidateExpressions: $invalidateExpressions,
			usedVariables: $usedVariables,
			acceptsNamedArguments: TrinaryLogic::createYes(),
			mustUseReturnValue: $mustUseReturnValue,
		);
	}

}
