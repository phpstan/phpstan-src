<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use ArrayAccess;
use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ConditionalExpressionHolder;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExpressionTypeHolder;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Node\VirtualNode;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use TypeError;
use function array_key_last;
use function array_merge;
use function array_pop;
use function array_reverse;
use function array_slice;
use function count;
use function in_array;
use function is_int;
use function is_string;

/**
 * @implements ExprHandler<Assign|AssignRef>
 */
#[AutowiredService]
final class AssignHandler implements ExprHandler
{

	public function __construct(
		private TypeSpecifier $typeSpecifier,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Assign || $expr instanceof AssignRef;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $scope->getType($expr->expr);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$result = $this->processAssignVar(
			$nodeScopeResolver,
			$scope,
			$storage,
			$stmt,
			$expr->var,
			$expr->expr,
			$nodeCallback,
			$context,
			static function (MutatingScope $scope) use ($stmt, $expr, $nodeCallback, $context, $storage, $nodeScopeResolver): ExpressionResult {
				$impurePoints = [];
				if ($expr instanceof AssignRef) {
					$referencedExpr = $expr->expr;
					while ($referencedExpr instanceof ArrayDimFetch) {
						$referencedExpr = $referencedExpr->var;
					}

					if ($referencedExpr instanceof PropertyFetch || $referencedExpr instanceof StaticPropertyFetch) {
						$impurePoints[] = new ImpurePoint(
							$scope,
							$expr,
							'propertyAssignByRef',
							'property assignment by reference',
							false,
						);
					}

					$scope = $scope->enterExpressionAssign($expr->expr);
				}

				if ($expr->var instanceof Variable && is_string($expr->var->name)) {
					$context = $context->enterRightSideAssign(
						$expr->var->name,
						$expr->expr,
					);
				}

				$nodeScopeResolver->storeBeforeScope($storage, $expr, $scope);
				$result = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $result->isAlwaysTerminating();
				$scope = $result->getScope();

				if ($expr instanceof AssignRef) {
					$scope = $scope->exitExpressionAssign($expr->expr);
				}

				return new ExpressionResult($scope, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
			},
			true,
		);
		$scope = $result->getScope();

		if (
			$expr instanceof AssignRef
			&& $expr->var instanceof Variable
			&& is_string($expr->var->name)
			&& $expr->expr instanceof Variable
			&& is_string($expr->expr->name)
		) {
			$varName = $expr->var->name;
			$refName = $expr->expr->name;
			$type = $scope->getType($expr->var);
			$nativeType = $scope->getNativeType($expr->var);

			// When $varName is assigned, update $refName
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($varName, new Variable($refName), new Variable($varName)),
				$type,
				$nativeType,
			);

			// When $refName is assigned, update $varName
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($refName, new Variable($varName), new Variable($refName)),
				$type,
				$nativeType,
			);
		}

		$vars = $nodeScopeResolver->getAssignedVariables($expr->var);
		if (count($vars) > 0) {
			$varChangedScope = false;
			$scope = $nodeScopeResolver->processVarAnnotation($scope, $vars, $stmt, $varChangedScope);
			if (!$varChangedScope) {
				$scope = $nodeScopeResolver->processStmtVarAnnotation($scope, $storage, $stmt, null, $nodeCallback);
			}
		}

		return new ExpressionResult(
			$scope,
			hasYield: $result->hasYield(),
			isAlwaysTerminating: $result->isAlwaysTerminating(),
			throwPoints: $result->getThrowPoints(),
			impurePoints: $result->getImpurePoints(),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 * @param Closure(MutatingScope $scope): ExpressionResult $processExprCallback
	 */
	public function processAssignVar(
		NodeScopeResolver $nodeScopeResolver,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		Node\Stmt $stmt,
		Expr $var,
		Expr $assignedExpr,
		callable $nodeCallback,
		ExpressionContext $context,
		Closure $processExprCallback,
		bool $enterExpressionAssign,
	): ExpressionResult
	{
		$nodeScopeResolver->callNodeCallback($nodeCallback, $var, $enterExpressionAssign ? $scope->enterExpressionAssign($var) : $scope, $storage);
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$isAssignOp = $assignedExpr instanceof Expr\AssignOp && !$enterExpressionAssign;
		if ($var instanceof Variable) {
			$nodeScopeResolver->storeBeforeScope($storage, $var, $scope);
			$result = $processExprCallback($scope);
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scopeBeforeAssignEval = $scope;
			$scope = $result->getScope();
			if (is_string($var->name)) {
				if (in_array($var->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
					$impurePoints[] = new ImpurePoint($scopeBeforeAssignEval, $var, 'superglobal', 'assign to superglobal variable', true);
				}
				$assignedExpr = $this->unwrapAssign($assignedExpr);
				$type = $scopeBeforeAssignEval->getType($assignedExpr);

				$conditionalExpressions = [];
				if ($assignedExpr instanceof Ternary) {
					$if = $assignedExpr->if;
					if ($if === null) {
						$if = $assignedExpr->cond;
					}
					$condScope = $nodeScopeResolver->processExprNode($stmt, $assignedExpr->cond, $scope, $storage->duplicate(), new NoopNodeCallback(), ExpressionContext::createDeep())->getScope();
					$truthySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($condScope, $assignedExpr->cond, TypeSpecifierContext::createTruthy());
					$falseySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($condScope, $assignedExpr->cond, TypeSpecifierContext::createFalsey());
					$truthyScope = $condScope->filterBySpecifiedTypes($truthySpecifiedTypes);
					$falsyScope = $condScope->filterBySpecifiedTypes($falseySpecifiedTypes);
					$truthyType = $truthyScope->getType($if);
					$falseyType = $falsyScope->getType($assignedExpr->else);

					if (
						$truthyType->isSuperTypeOf($falseyType)->no()
						&& $falseyType->isSuperTypeOf($truthyType)->no()
					) {
						$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($condScope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType, $impurePoints, $assignedExpr);
						$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($condScope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType, $impurePoints, $assignedExpr);
						$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($condScope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType, $impurePoints, $assignedExpr);
						$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($condScope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType, $impurePoints, $assignedExpr);
					}
				}

				$truthyType = TypeCombinator::removeFalsey($type);
				if ($truthyType !== $type) {
					$truthySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $assignedExpr, TypeSpecifierContext::createTruthy());
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType, $impurePoints, $assignedExpr);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType, $impurePoints, $assignedExpr);

					$falseyType = TypeCombinator::intersect($type, StaticTypeFactory::falsey());
					$falseySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $assignedExpr, TypeSpecifierContext::createFalsey());
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType, $impurePoints, $assignedExpr);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType, $impurePoints, $assignedExpr);
				}

				foreach ([null, false, 0, 0.0, '', '0', []] as $falseyScalar) {
					$falseyType = ConstantTypeHelper::getTypeFromValue($falseyScalar);
					$withoutFalseyType = TypeCombinator::remove($type, $falseyType);
					if (
						$withoutFalseyType->equals($type)
						|| $withoutFalseyType->equals($truthyType)
					) {
						continue;
					}

					if ($falseyScalar === null) {
						$astNode = new ConstFetch(new Name('null'));
					} elseif ($falseyScalar === false) {
						$astNode = new ConstFetch(new Name('false'));
					} elseif ($falseyScalar === 0) {
						$astNode = new Node\Scalar\Int_($falseyScalar);
					} elseif ($falseyScalar === 0.0) {
						$astNode = new Node\Scalar\Float_($falseyScalar);
					} elseif (in_array($falseyScalar, ['', '0'], true)) {
						$astNode = new Node\Scalar\String_($falseyScalar);
					} elseif ($falseyScalar === []) {
						$astNode = new Node\Expr\Array_($falseyScalar);
					}

					$notIdenticalConditionExpr = new Expr\BinaryOp\NotIdentical($assignedExpr, $astNode);
					$notIdenticalSpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $notIdenticalConditionExpr, TypeSpecifierContext::createTrue());
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $notIdenticalSpecifiedTypes, $withoutFalseyType, $impurePoints, $assignedExpr);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $notIdenticalSpecifiedTypes, $withoutFalseyType, $impurePoints, $assignedExpr);

					$identicalConditionExpr = new Expr\BinaryOp\Identical($assignedExpr, $astNode);
					$identicalSpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $identicalConditionExpr, TypeSpecifierContext::createTrue());
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $identicalSpecifiedTypes, $falseyType, $impurePoints, $assignedExpr);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $identicalSpecifiedTypes, $falseyType, $impurePoints, $assignedExpr);
				}

				$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedExpr), $scopeBeforeAssignEval, $storage);
				$scope = $scope->assignVariable($var->name, $type, $scope->getNativeType($assignedExpr), TrinaryLogic::createYes());
				foreach ($conditionalExpressions as $exprString => $holders) {
					$scope = $scope->addConditionalExpressions((string) $exprString, $holders);
				}

				if ($assignedExpr instanceof Expr\Array_) {
					$scope = $this->processArrayByRefItems($scope, $var->name, $assignedExpr, new Variable($var->name));
				}
			} else {
				$nameExprResult = $nodeScopeResolver->processExprNode($stmt, $var->name, $scope, $storage, $nodeCallback, $context);
				$hasYield = $hasYield || $nameExprResult->hasYield();
				$throwPoints = array_merge($throwPoints, $nameExprResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $nameExprResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $nameExprResult->isAlwaysTerminating();
				$scope = $nameExprResult->getScope();
			}
		} elseif ($var instanceof ArrayDimFetch) {
			$dimFetchStack = [];
			$originalVar = $var;
			$assignedPropertyExpr = $assignedExpr;
			while ($var instanceof ArrayDimFetch) {
				$varForSetOffsetValue = $var->var;
				if ($varForSetOffsetValue instanceof PropertyFetch || $varForSetOffsetValue instanceof StaticPropertyFetch) {
					$varForSetOffsetValue = new OriginalPropertyTypeExpr($varForSetOffsetValue);
				}

				if (
					$var === $originalVar
					&& $var->dim !== null
					&& $scope->hasExpressionType($var)->yes()
				) {
					$assignedPropertyExpr = new SetExistingOffsetValueTypeExpr(
						$varForSetOffsetValue,
						$var->dim,
						$assignedPropertyExpr,
					);
				} else {
					$assignedPropertyExpr = new SetOffsetValueTypeExpr(
						$varForSetOffsetValue,
						$var->dim,
						$assignedPropertyExpr,
					);
				}
				$dimFetchStack[] = $var;
				$var = $var->var;
			}

			// 1. eval root expr
			if ($enterExpressionAssign) {
				$scope = $scope->enterExpressionAssign($var);
			}
			$result = $nodeScopeResolver->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
			if ($enterExpressionAssign) {
				$scope = $scope->exitExpressionAssign($var);
			}

			// 2. eval dimensions
			$offsetTypes = [];
			$offsetNativeTypes = [];
			$dimFetchStack = array_reverse($dimFetchStack);
			$lastDimKey = array_key_last($dimFetchStack);
			foreach ($dimFetchStack as $key => $dimFetch) {
				$dimExpr = $dimFetch->dim;

				// Callback was already called for last dim at the beginning of the method.
				if ($key !== $lastDimKey) {
					$nodeScopeResolver->callNodeCallback($nodeCallback, $dimFetch, $enterExpressionAssign ? $scope->enterExpressionAssign($dimFetch) : $scope, $storage);
				}

				if ($dimExpr === null) {
					$offsetTypes[] = [null, $dimFetch];
					$offsetNativeTypes[] = [null, $dimFetch];
					$nodeScopeResolver->storeBeforeScope($storage, $dimFetch, $scope);

				} else {
					$offsetTypes[] = [$scope->getType($dimExpr), $dimFetch];
					$offsetNativeTypes[] = [$scope->getNativeType($dimExpr), $dimFetch];

					if ($enterExpressionAssign) {
						$scope->enterExpressionAssign($dimExpr);
					}
					$nodeScopeResolver->storeBeforeScope($storage, $dimFetch, $scope);
					$result = $nodeScopeResolver->processExprNode($stmt, $dimExpr, $scope, $storage, $nodeCallback, $context->enterDeep());
					$hasYield = $hasYield || $result->hasYield();
					$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
					$scope = $result->getScope();

					if ($enterExpressionAssign) {
						$scope = $scope->exitExpressionAssign($dimExpr);
					}
				}
			}

			$valueToWrite = $scope->getType($assignedExpr);
			$nativeValueToWrite = $scope->getNativeType($assignedExpr);
			$scopeBeforeAssignEval = $scope;

			// 3. eval assigned expr
			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();

			$varType = $scope->getType($var);
			$varNativeType = $scope->getNativeType($var);

			// 4. compose types
			$isImplicitArrayCreation = $this->isImplicitArrayCreation($dimFetchStack, $scope);
			if ($isImplicitArrayCreation->yes()) {
				$varType = new ConstantArrayType([], []);
				$varNativeType = new ConstantArrayType([], []);
			}
			$offsetValueType = $varType;
			$offsetNativeValueType = $varNativeType;

			[$valueToWrite, $additionalExpressions] = $this->produceArrayDimFetchAssignValueToWrite($dimFetchStack, $offsetTypes, $offsetValueType, $valueToWrite, $scope);

			if (!$offsetValueType->equals($offsetNativeValueType) || !$valueToWrite->equals($nativeValueToWrite)) {
				[$nativeValueToWrite, $additionalNativeExpressions] = $this->produceArrayDimFetchAssignValueToWrite($dimFetchStack, $offsetNativeTypes, $offsetNativeValueType, $nativeValueToWrite, $scope);
			} else {
				$rewritten = false;
				foreach ($offsetTypes as $i => [$offsetType]) {
					[$offsetNativeType] = $offsetNativeTypes[$i];

					if ($offsetType === null) {
						if ($offsetNativeType !== null) {
							throw new ShouldNotHappenException();
						}

						continue;
					} elseif ($offsetNativeType === null) {
						throw new ShouldNotHappenException();
					}
					if ($offsetType->equals($offsetNativeType)) {
						continue;
					}

					[$nativeValueToWrite] = $this->produceArrayDimFetchAssignValueToWrite($dimFetchStack, $offsetNativeTypes, $offsetNativeValueType, $nativeValueToWrite, $scope);
					$rewritten = true;
					break;
				}

				if (!$rewritten) {
					$nativeValueToWrite = $valueToWrite;
				}
			}

			if ($varType->isArray()->yes() || !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->yes()) {
				if ($var instanceof Variable && is_string($var->name)) {
					$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($var, new TypeExpr($valueToWrite)), $scopeBeforeAssignEval, $storage);
					$scope = $scope->assignVariable($var->name, $valueToWrite, $nativeValueToWrite, TrinaryLogic::createYes());
				} else {
					if ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
						$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
						if ($var instanceof PropertyFetch && $var->name instanceof Node\Identifier && !$isAssignOp) {
							$scope = $scope->assignInitializedProperty($scope->getType($var->var), $var->name->toString());
						}
					}
					$scope = $scope->assignExpression(
						$var,
						$valueToWrite,
						$nativeValueToWrite,
					);
				}
			} else {
				if ($var instanceof Variable) {
					$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedPropertyExpr), $scopeBeforeAssignEval, $storage);
				} elseif ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
					$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
					if ($var instanceof PropertyFetch && $var->name instanceof Node\Identifier && !$isAssignOp) {
						$scope = $scope->assignInitializedProperty($scope->getType($var->var), $var->name->toString());
					}
				}
			}

			foreach ($additionalExpressions as $k => $additionalExpression) {
				[$expr, $type] = $additionalExpression;
				$nativeType = $type;
				if (isset($additionalNativeExpressions[$k])) {
					[, $nativeType] = $additionalNativeExpressions[$k];
				}

				$scope = $scope->assignExpression($expr, $type, $nativeType);
			}

			$setVarType = $scope->getType($originalVar->var);
			if (
				!$setVarType instanceof ErrorType
				&& !$setVarType->isArray()->yes()
				&& !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($setVarType)->no()
			) {
				$throwPoints = array_merge($throwPoints, $nodeScopeResolver->processExprNode(
					$stmt,
					new MethodCall(new TypeExpr($setVarType), 'offsetSet'),
					$scope,
					$storage,
					new NoopNodeCallback(),
					$context,
				)->getThrowPoints());
			}
		} elseif ($var instanceof PropertyFetch) {
			$nodeScopeResolver->storeBeforeScope($storage, $var, $scope);
			$objectResult = $nodeScopeResolver->processExprNode($stmt, $var->var, $scope, $storage, $nodeCallback, $context);
			$hasYield = $objectResult->hasYield();
			$throwPoints = $objectResult->getThrowPoints();
			$impurePoints = $objectResult->getImpurePoints();
			$isAlwaysTerminating = $objectResult->isAlwaysTerminating();
			$scope = $objectResult->getScope();

			$propertyName = null;
			if ($var->name instanceof Node\Identifier) {
				$propertyName = $var->name->name;
			} else {
				$propertyNameResult = $nodeScopeResolver->processExprNode($stmt, $var->name, $scope, $storage, $nodeCallback, $context);
				$hasYield = $hasYield || $propertyNameResult->hasYield();
				$throwPoints = array_merge($throwPoints, $propertyNameResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $propertyNameResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $propertyNameResult->isAlwaysTerminating();
				$scope = $propertyNameResult->getScope();
			}

			$scopeBeforeAssignEval = $scope;
			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();

			if ($var->name instanceof Expr && $this->phpVersion->supportsPropertyHooks()) {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $var);
			}

			$propertyHolderType = $scope->getType($var->var);
			if ($propertyName !== null && $propertyHolderType->hasInstanceProperty($propertyName)->yes()) {
				$propertyReflection = $propertyHolderType->getInstanceProperty($propertyName, $scope);
				$assignedExprType = $scope->getType($assignedExpr);
				$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				if ($propertyReflection->canChangeTypeAfterAssignment()) {
					if ($propertyReflection->hasNativeType()) {
						$propertyNativeType = $propertyReflection->getNativeType();

						$assignedTypeIsCompatible = $propertyNativeType->isSuperTypeOf($assignedExprType)->yes();
						if (!$assignedTypeIsCompatible) {
							foreach (TypeUtils::flattenTypes($propertyNativeType) as $type) {
								if ($type->isSuperTypeOf($assignedExprType)->yes()) {
									$assignedTypeIsCompatible = true;
									break;
								}
							}
						}

						if ($assignedTypeIsCompatible) {
							$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
						} else {
							$scope = $scope->assignExpression(
								$var,
								TypeCombinator::intersect($assignedExprType->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
								TypeCombinator::intersect($scope->getNativeType($assignedExpr)->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
							);
						}
					} else {
						$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
					}
				}
				$declaringClass = $propertyReflection->getDeclaringClass();
				if ($declaringClass->hasNativeProperty($propertyName)) {
					$nativeProperty = $declaringClass->getNativeProperty($propertyName);
					$propertyNativeType = $nativeProperty->getNativeType();

					$assignedTypeIsCompatible = $propertyNativeType->isSuperTypeOf($assignedExprType)->yes();
					if (!$assignedTypeIsCompatible && !$assignedExprType instanceof MixedType) {
						foreach (TypeUtils::flattenTypes($assignedExprType->toCoercedArgumentType(true)) as $type) {
							$accepts = $propertyNativeType->accepts($type, true);
							if ($accepts->yes()) {
								$assignedTypeIsCompatible = true;
								continue;
							}
							$assignedTypeIsCompatible = false;
							break;
						}
					}

					if (
						!$assignedTypeIsCompatible
					) {
						$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(TypeError::class), $assignedExpr, false);
					}
					if ($this->phpVersion->supportsPropertyHooks()) {
						$throwPoints = array_merge($throwPoints, $nodeScopeResolver->getThrowPointsFromPropertyHook($scope, $var, $nativeProperty, 'set'));
					}
					if ($enterExpressionAssign) {
						$scope = $scope->assignInitializedProperty($propertyHolderType, $propertyName);
					}
				}
			} else {
				// fallback
				$assignedExprType = $scope->getType($assignedExpr);
				$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
				// simulate dynamic property assign by __set to get throw points
				if (!$propertyHolderType->hasMethod('__set')->no()) {
					$throwPoints = array_merge($throwPoints, $nodeScopeResolver->processExprNode(
						$stmt,
						new MethodCall($var->var, '__set'),
						$scope,
						$storage,
						new NoopNodeCallback(),
						$context,
					)->getThrowPoints());
				}
			}

		} elseif ($var instanceof Expr\StaticPropertyFetch) {
			$nodeScopeResolver->storeBeforeScope($storage, $var, $scope);
			if ($var->class instanceof Node\Name) {
				$propertyHolderType = $scope->resolveTypeByName($var->class);
			} else {
				$nodeScopeResolver->processExprNode($stmt, $var->class, $scope, $storage, $nodeCallback, $context);
				$propertyHolderType = $scope->getType($var->class);
			}

			$propertyName = null;
			if ($var->name instanceof Node\Identifier) {
				$propertyName = $var->name->name;
			} else {
				$propertyNameResult = $nodeScopeResolver->processExprNode($stmt, $var->name, $scope, $storage, $nodeCallback, $context);
				$hasYield = $propertyNameResult->hasYield();
				$throwPoints = $propertyNameResult->getThrowPoints();
				$impurePoints = $propertyNameResult->getImpurePoints();
				$isAlwaysTerminating = $propertyNameResult->isAlwaysTerminating();
				$scope = $propertyNameResult->getScope();
			}

			$scopeBeforeAssignEval = $scope;
			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();

			if ($propertyName !== null) {
				$propertyReflection = $scope->getStaticPropertyReflection($propertyHolderType, $propertyName);
				$assignedExprType = $scope->getType($assignedExpr);
				$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				if ($propertyReflection !== null && $propertyReflection->canChangeTypeAfterAssignment()) {
					if ($propertyReflection->hasNativeType()) {
						$propertyNativeType = $propertyReflection->getNativeType();
						$assignedTypeIsCompatible = $propertyNativeType->isSuperTypeOf($assignedExprType)->yes();

						if (!$assignedTypeIsCompatible) {
							foreach (TypeUtils::flattenTypes($propertyNativeType) as $type) {
								if ($type->isSuperTypeOf($assignedExprType)->yes()) {
									$assignedTypeIsCompatible = true;
									break;
								}
							}
						}

						if ($assignedTypeIsCompatible) {
							$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
						} else {
							$scope = $scope->assignExpression(
								$var,
								TypeCombinator::intersect($assignedExprType->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
								TypeCombinator::intersect($scope->getNativeType($assignedExpr)->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
							);
						}
					} else {
						$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
					}
				}
			} else {
				// fallback
				$assignedExprType = $scope->getType($assignedExpr);
				$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
			}
		} elseif ($var instanceof List_) {
			$nodeScopeResolver->storeBeforeScope($storage, $var, $scope);
			$result = $processExprCallback($scope);
			$hasYield = $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
			foreach ($var->items as $i => $arrayItem) {
				if ($arrayItem === null) {
					continue;
				}

				$itemScope = $scope;
				if ($enterExpressionAssign) {
					$itemScope = $itemScope->enterExpressionAssign($arrayItem->value);
				}
				$itemScope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($itemScope, $arrayItem->value);
				$nodeScopeResolver->callNodeCallback($nodeCallback, $arrayItem, $itemScope, $storage);
				if ($arrayItem->key !== null) {
					$keyResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $itemScope, $storage, $nodeCallback, $context->enterDeep());
					$hasYield = $hasYield || $keyResult->hasYield();
					$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
					$isAlwaysTerminating = $isAlwaysTerminating || $keyResult->isAlwaysTerminating();
					$scope = $keyResult->getScope();
				}

				if ($arrayItem->key === null) {
					$dimExpr = new Node\Scalar\Int_($i);
				} else {
					$dimExpr = $arrayItem->key;
				}
				$result = $this->processAssignVar(
					$nodeScopeResolver,
					$scope,
					$storage,
					$stmt,
					$arrayItem->value,
					new GetOffsetValueTypeExpr($assignedExpr, $dimExpr),
					$nodeCallback,
					$context,
					static fn (MutatingScope $scope): ExpressionResult => new ExpressionResult($scope, hasYield: false, isAlwaysTerminating: false, throwPoints: [], impurePoints: []),
					$enterExpressionAssign,
				);
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			}
		} elseif ($var instanceof ExistingArrayDimFetch) {
			$dimFetchStack = [];
			$assignedPropertyExpr = $assignedExpr;
			while ($var instanceof ExistingArrayDimFetch) {
				$varForSetOffsetValue = $var->getVar();
				if ($varForSetOffsetValue instanceof PropertyFetch || $varForSetOffsetValue instanceof StaticPropertyFetch) {
					$varForSetOffsetValue = new OriginalPropertyTypeExpr($varForSetOffsetValue);
				}
				$assignedPropertyExpr = new SetExistingOffsetValueTypeExpr(
					$varForSetOffsetValue,
					$var->getDim(),
					$assignedPropertyExpr,
				);
				$dimFetchStack[] = $var;
				$var = $var->getVar();
			}

			$offsetTypes = [];
			$offsetNativeTypes = [];
			foreach (array_reverse($dimFetchStack) as $dimFetch) {
				$dimExpr = $dimFetch->getDim();
				$offsetTypes[] = [$scope->getType($dimExpr), $dimFetch];
				$offsetNativeTypes[] = [$scope->getNativeType($dimExpr), $dimFetch];
			}

			$valueToWrite = $scope->getType($assignedExpr);
			$nativeValueToWrite = $scope->getNativeType($assignedExpr);
			$varType = $scope->getType($var);
			$varNativeType = $scope->getNativeType($var);

			$offsetValueType = $varType;
			$offsetNativeValueType = $varNativeType;
			$offsetValueTypeStack = [$offsetValueType];
			$offsetValueNativeTypeStack = [$offsetNativeValueType];
			foreach (array_slice($offsetTypes, 0, -1) as [$offsetType]) {
				$offsetValueType = $offsetValueType->getOffsetValueType($offsetType);
				$offsetValueTypeStack[] = $offsetValueType;
			}
			foreach (array_slice($offsetNativeTypes, 0, -1) as [$offsetNativeType]) {
				$offsetNativeValueType = $offsetNativeValueType->getOffsetValueType($offsetNativeType);
				$offsetValueNativeTypeStack[] = $offsetNativeValueType;
			}

			foreach (array_reverse($offsetTypes) as [$offsetType]) {
				/** @var Type $offsetValueType */
				$offsetValueType = array_pop($offsetValueTypeStack);
				$valueToWrite = $offsetValueType->setExistingOffsetValueType($offsetType, $valueToWrite);
			}
			foreach (array_reverse($offsetNativeTypes) as [$offsetNativeType]) {
				/** @var Type $offsetNativeValueType */
				$offsetNativeValueType = array_pop($offsetValueNativeTypeStack);
				$nativeValueToWrite = $offsetNativeValueType->setExistingOffsetValueType($offsetNativeType, $nativeValueToWrite);
			}

			if ($var instanceof Variable && is_string($var->name)) {
				$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedPropertyExpr), $scope, $storage);
				$scope = $scope->assignVariable($var->name, $valueToWrite, $nativeValueToWrite, TrinaryLogic::createYes());
			} else {
				if ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
					$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scope, $storage);
				}
				$scope = $scope->assignExpression(
					$var,
					$valueToWrite,
					$nativeValueToWrite,
				);
			}
		} else {
			$varResult = $nodeScopeResolver->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, $context);
			$hasYield = $varResult->hasYield();
			$throwPoints = array_merge($throwPoints, $varResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $varResult->getImpurePoints());
			$isAlwaysTerminating = $varResult->isAlwaysTerminating();
			$scope = $varResult->getScope();
			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();
		}

		// stored where processAssignVar is called
		return new ExpressionResult($scope, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
	}

	private function unwrapAssign(Expr $expr): Expr
	{
		if ($expr instanceof Assign) {
			return $this->unwrapAssign($expr->expr);
		}

		return $expr;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param ImpurePoint[] $rhsImpurePoints
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function processSureTypesForConditionalExpressionsAfterAssign(Scope $scope, string $variableName, array $conditionalExpressions, SpecifiedTypes $specifiedTypes, Type $variableType, array $rhsImpurePoints, Expr $assignedExpr): array
	{
		foreach ($specifiedTypes->getSureTypes() as $exprString => [$expr, $exprType]) {
			if (!$this->isExprSafeToProjectThroughVariable($expr, $variableName, $rhsImpurePoints, $assignedExpr)) {
				continue;
			}

			$exprString = (string) $exprString;

			if (!isset($conditionalExpressions[$exprString])) {
				$conditionalExpressions[$exprString] = [];
			}

			$holder = new ConditionalExpressionHolder([
				'$' . $variableName => ExpressionTypeHolder::createYes(new Variable($variableName), $variableType),
			], ExpressionTypeHolder::createYes(
				$expr,
				TypeCombinator::intersect($scope->getType($expr), $exprType),
			));
			$conditionalExpressions[$exprString][$holder->getKey()] = $holder;
		}

		return $conditionalExpressions;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param ImpurePoint[] $rhsImpurePoints
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function processSureNotTypesForConditionalExpressionsAfterAssign(Scope $scope, string $variableName, array $conditionalExpressions, SpecifiedTypes $specifiedTypes, Type $variableType, array $rhsImpurePoints, Expr $assignedExpr): array
	{
		foreach ($specifiedTypes->getSureNotTypes() as $exprString => [$expr, $exprType]) {
			if (!$this->isExprSafeToProjectThroughVariable($expr, $variableName, $rhsImpurePoints, $assignedExpr)) {
				continue;
			}

			$exprString = (string) $exprString;

			if (!isset($conditionalExpressions[$exprString])) {
				$conditionalExpressions[$exprString] = [];
			}

			$holder = new ConditionalExpressionHolder([
				'$' . $variableName => ExpressionTypeHolder::createYes(new Variable($variableName), $variableType),
			], ExpressionTypeHolder::createYes(
				$expr,
				TypeCombinator::remove($scope->getType($expr), $exprType),
			));
			$conditionalExpressions[$exprString][$holder->getKey()] = $holder;
		}

		return $conditionalExpressions;
	}

	/**
	 * We're about to remember "when $variableName is truthy/falsy, $expr has a narrower type".
	 * Whether that's safe to project forward depends on whether re-evaluating $expr later will
	 * still return the same value as when we observed the narrowing — i.e. whether $expr is
	 * referentially transparent with respect to the intervening code.
	 *
	 * Scalar/const-fetch literals are never narrowing targets, so skip them up front (they also
	 * happen to stringify to numeric exprStrings which collide with PHP's numeric-string
	 * array-key autocast).
	 *
	 * A plain variable is always safe: reading it doesn't produce side effects, and if it gets
	 * reassigned the existing conditional-expression-holder machinery invalidates the binding.
	 * This case matters for e.g. `$ok = preg_match(..., $matches); if ($ok) { use $matches }` —
	 * `preg_match` itself has impure points, but `$matches` is a plain variable and the
	 * narrowing attached to it should still survive.
	 *
	 * Other common tracked expressions (property/dim fetches, function/method calls) can always
	 * carry narrowings: PHPStan already memoises their types per exprString, and condition
	 * checks like `$x = $obj->foo() !== null; if ($x) { $obj->foo(); }` rely on this even when
	 * the RHS itself has impure points (as a method call without @phpstan-pure always does).
	 *
	 * Anything else is accepted only when the right-hand side evaluation recorded zero impure
	 * points — in that case all sub-expressions it produced sure types for were evaluated
	 * without side effects and can be re-evaluated later with the same result.
	 *
	 * @param ImpurePoint[] $rhsImpurePoints
	 */
	private function isExprSafeToProjectThroughVariable(Expr $expr, string $variableName, array $rhsImpurePoints, Expr $assignedExpr): bool
	{
		// Scalar/const-fetch literals and PHPStan virtual nodes (e.g. NativeTypeExpr) are never
		// narrowing targets at a usage site — skip them so they don't collide with PHP's
		// numeric-string array-key autocast or leak internal virtual expressions into the
		// conditional-expression map.
		if ($expr instanceof Node\Scalar || $expr instanceof ConstFetch || $expr instanceof VirtualNode || $expr instanceof Expr\UnaryMinus && $expr->expr instanceof Node\Scalar) {
			return false;
		}

		if ($expr instanceof Variable) {
			return is_string($expr->name) && $expr->name !== $variableName;
		}

		if (
			$expr instanceof PropertyFetch
			|| $expr instanceof ArrayDimFetch
		) {
			return true;
		}

		if (
			$expr instanceof FuncCall
			|| $expr instanceof MethodCall
			|| $expr instanceof NullsafeMethodCall
			|| $expr instanceof StaticCall
		) {
			// A call's type can change between evaluations. We're willing to project the
			// narrowing through a stored boolean only when the sure-type expression is a
			// *sub*-expression of the assigned RHS — e.g. `$ok = $x->foo() !== null` builds
			// a sure type for the sub-call `$x->foo()`. In that case the RHS as a whole
			// carries the comparison result, and later `if ($ok)` usefully re-narrows the
			// remembered sub-call. When the sure-type expression IS the whole RHS (e.g.
			// `$device = $this->nullable(); if ($device === null) { … }` with the
			// falsey-scalar loop producing `$this->nullable() === null` narrowings), the
			// projection would survive across subsequent reassignments of the target
			// expression and wrongly re-narrow fresh calls — so skip it.
			return $expr !== $assignedExpr;
		}

		return count($rhsImpurePoints) === 0;
	}

	/**
	 * @param list<ArrayDimFetch> $dimFetchStack
	 */
	private function isImplicitArrayCreation(array $dimFetchStack, Scope $scope): TrinaryLogic
	{
		if (count($dimFetchStack) === 0) {
			return TrinaryLogic::createNo();
		}

		$varNode = $dimFetchStack[0]->var;
		if (!$varNode instanceof Variable) {
			return TrinaryLogic::createNo();
		}

		if (!is_string($varNode->name)) {
			return TrinaryLogic::createNo();
		}

		return $scope->hasVariableType($varNode->name)->negate();
	}

	private function processArrayByRefItems(MutatingScope $scope, string $rootVarName, Expr\Array_ $arrayExpr, Expr $parentExpr): MutatingScope
	{
		$implicitIndex = 0;
		foreach ($arrayExpr->items as $arrayItem) {
			if ($arrayItem->key !== null) {
				$keyType = $scope->getType($arrayItem->key)->toArrayKey();

				if ($implicitIndex !== null) {
					$keyValues = $keyType->getConstantScalarValues();
					if (count($keyValues) === 1) {
						$keyValue = $keyValues[0];
						if (is_int($keyValue) && $keyValue >= $implicitIndex) {
							$implicitIndex = $keyValue + 1;
						}
					} elseif (!$keyType->isInteger()->no()) {
						// Key could be an integer, but we don't know which one,
						// so subsequent implicit indices are unpredictable
						$implicitIndex = null;
					}
				}

				$dimExpr = $arrayItem->key;
			} elseif ($implicitIndex !== null) {
				$dimExpr = new Node\Scalar\Int_($implicitIndex);
				$implicitIndex++;
			} else {
				$dimExpr = new TypeExpr(new IntegerType());
			}

			if ($arrayItem->value instanceof Expr\Array_) {
				$dimFetchExpr = new ArrayDimFetch($parentExpr, $dimExpr);
				$scope = $this->processArrayByRefItems($scope, $rootVarName, $arrayItem->value, $dimFetchExpr);
			}

			if (!$arrayItem->byRef || !$arrayItem->value instanceof Variable || !is_string($arrayItem->value->name)) {
				continue;
			}

			$refVarName = $arrayItem->value->name;
			$dimFetchExpr = new ArrayDimFetch($parentExpr, $dimExpr);
			$refType = $scope->getType(new Variable($refVarName));
			$refNativeType = $scope->getNativeType(new Variable($refVarName));

			// When $rootVarName's array key changes, update $refVarName
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($rootVarName, new Variable($refVarName), $dimFetchExpr),
				$refType,
				$refNativeType,
			);

			// When $refVarName changes, update $rootVarName's array key
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($refVarName, $dimFetchExpr, new Variable($refVarName)),
				$refType,
				$refNativeType,
			);
		}

		return $scope;
	}

	/**
	 * @param non-empty-list<ArrayDimFetch> $dimFetchStack
	 * @param non-empty-list<array{Type|null, ArrayDimFetch}> $offsetTypes
	 *
	 * @return array{Type, list<array{Expr, Type}>}
	 */
	private function produceArrayDimFetchAssignValueToWrite(array $dimFetchStack, array $offsetTypes, Type $offsetValueType, Type $valueToWrite, Scope $scope): array
	{
		$originalValueToWrite = $valueToWrite;

		$offsetValueTypeStack = [$offsetValueType];
		$generalizeOnWrite = $offsetTypes[array_key_last($offsetTypes)][0] !== null;
		foreach (array_slice($offsetTypes, 0, -1) as [$offsetType, $dimFetch]) {
			if ($offsetType === null) {
				$offsetValueType = new ConstantArrayType([], []);
				$generalizeOnWrite = false;
			} else {
				$has = $offsetValueType->hasOffsetValueType($offsetType);
				if ($has->yes()) {
					if ($scope->hasExpressionType($dimFetch)->yes()) {
						$offsetValueType = $scope->getType($dimFetch);
					} else {
						$offsetValueType = $offsetValueType->getOffsetValueType($offsetType);
					}
				} elseif ($has->maybe()) {
					if ($scope->hasExpressionType($dimFetch)->yes()) {
						$generalizeOnWrite = false;
						$offsetValueType = $scope->getType($dimFetch);
					} else {
						$offsetValueType = TypeCombinator::union($offsetValueType->getOffsetValueType($offsetType), new ConstantArrayType([], []));
					}
				} else {
					$generalizeOnWrite = false;
					$offsetValueType = new ConstantArrayType([], []);
				}
			}

			$offsetValueTypeStack[] = $offsetValueType;
		}

		$lastDimKey = array_key_last($dimFetchStack);
		$computedContainerValues = [];
		foreach (array_reverse($offsetTypes) as $i => [$offsetType]) {
			/** @var Type $offsetValueType */
			$offsetValueType = array_pop($offsetValueTypeStack);
			if (
				!$offsetValueType instanceof MixedType
				&& !$offsetValueType->isArray()->yes()
			) {
				if ($offsetType !== null && $offsetType->isInteger()->yes()) {
					$offsetValueType = TypeCombinator::intersect($offsetValueType, StaticTypeFactory::intOffsetAccessibleType());
				} else {
					$offsetValueType = TypeCombinator::intersect($offsetValueType, StaticTypeFactory::generalOffsetAccessibleType());
				}
			}

			$arrayDimFetch = $dimFetchStack[$i] ?? null;
			if (
				$offsetType !== null
				&& $arrayDimFetch !== null
				&& $scope->hasExpressionType($arrayDimFetch)->yes()
				&& !$offsetValueType->hasOffsetValueType($offsetType)->no()
			) {
				$hasOffsetType = null;
				if ($offsetType instanceof ConstantStringType || $offsetType instanceof ConstantIntegerType) {
					$hasOffsetType = new HasOffsetValueType($offsetType, $valueToWrite);
				}
				$valueToWrite = $offsetValueType->setExistingOffsetValueType($offsetType, $valueToWrite);

				if ($valueToWrite->isArray()->yes()) {
					if ($hasOffsetType !== null) {
						$valueToWrite = TypeCombinator::intersect(
							$valueToWrite,
							$hasOffsetType,
						);
					} else {
						$valueToWrite = TypeCombinator::intersect(
							$valueToWrite,
							new NonEmptyArrayType(),
						);
					}
				}

			} else {
				// when $unionValues=false the array item-type will be replaced with $valueToWrite
				// when $unionValues=true the existing array item-type will be union'ed with $valueToWrite -> type gets wider
				$unionValues = false;
				if ($i === 0) {
					$unionValues = true;
				} elseif (
					$generalizeOnWrite
					&& $i === count($offsetTypes) - 1
					&&
						(
							$originalValueToWrite->isConstantScalarValue()->yes()
							|| !$offsetValueType->getIterableValueType()->isSuperTypeOf($valueToWrite)->yes()
						)
				) {
					$unionValues = true;
				}

				$valueToWrite = $offsetValueType->setOffsetValueType($offsetType, $valueToWrite, $unionValues);
			}

			if ($arrayDimFetch !== null && $offsetValueType->isList()->yes() && $this->shouldKeepList($arrayDimFetch, $scope, $offsetValueType)) {
				$valueToWrite = TypeCombinator::intersect($valueToWrite, new AccessoryArrayListType());
			}

			$containerKey = $lastDimKey - $i - 1;
			if ($containerKey < 0) {
				continue;
			}

			$computedContainerValues[$containerKey] = $valueToWrite;
		}

		$additionalExpressions = [];
		foreach ($dimFetchStack as $key => $dimFetch) {
			if ($dimFetch->dim === null) {
				continue;
			}

			if ($key === $lastDimKey) {
				$additionalValueType = $originalValueToWrite;
			} elseif (isset($computedContainerValues[$key])) {
				$additionalValueType = $computedContainerValues[$key];
			} else {
				$offsetType = $scope->getType($dimFetch->dim);
				$additionalValueType = $valueToWrite->getOffsetValueType($offsetType);
			}

			$additionalExpressions[] = [$dimFetch, $additionalValueType];
		}

		return [$valueToWrite, $additionalExpressions];
	}

	private function shouldKeepList(ArrayDimFetch $arrayDimFetch, Scope $scope, Type $offsetValueType): bool
	{
		if ($arrayDimFetch->dim instanceof Expr\BinaryOp\Plus) {
			if ( // keep list for $list[$index + 1] assignments
				$arrayDimFetch->dim->right instanceof Variable
				&& $arrayDimFetch->dim->left instanceof Node\Scalar\Int_
				&& $arrayDimFetch->dim->left->value === 1
				&& $scope->hasExpressionType(new ArrayDimFetch($arrayDimFetch->var, $arrayDimFetch->dim->right))->yes()
			) {
				return true;
			} elseif ( // keep list for $list[1 + $index] assignments
				$arrayDimFetch->dim->left instanceof Variable
				&& $arrayDimFetch->dim->right instanceof Node\Scalar\Int_
				&& $arrayDimFetch->dim->right->value === 1
				&& $scope->hasExpressionType(new ArrayDimFetch($arrayDimFetch->var, $arrayDimFetch->dim->left))->yes()
			) {
				return true;
			}
		} elseif ( // keep list for $list[count($list) - n] assignments
			$arrayDimFetch->dim instanceof Expr\BinaryOp\Minus
			&& $arrayDimFetch->dim->right instanceof Node\Scalar\Int_
			&& $arrayDimFetch->dim->left instanceof Expr\FuncCall
			&& $arrayDimFetch->dim->left->name instanceof Name
			&& !$arrayDimFetch->dim->left->isFirstClassCallable()
			&& in_array($arrayDimFetch->dim->left->name->toLowerString(), ['count', 'sizeof'], true)
			&& count($arrayDimFetch->dim->left->getArgs()) === 1 // could support COUNT_RECURSIVE, COUNT_NORMAL
			&& $this->isSameVariable($arrayDimFetch->var, $arrayDimFetch->dim->left->getArgs()[0]->value)
			&& IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($scope->getType($arrayDimFetch->dim))->yes()
			&& $offsetValueType->isIterableAtLeastOnce()->yes()
		) {
			return true;
		} elseif ( // keep list for $list[array_key_last($list)] and $list[array_key_first($list)] assignments
			$arrayDimFetch->dim instanceof Expr\FuncCall
			&& $arrayDimFetch->dim->name instanceof Name
			&& !$arrayDimFetch->dim->isFirstClassCallable()
			&& in_array($arrayDimFetch->dim->name->toLowerString(), ['array_key_last', 'array_key_first'], true)
			&& count($arrayDimFetch->dim->getArgs()) >= 1
			&& $this->isSameVariable($arrayDimFetch->var, $arrayDimFetch->dim->getArgs()[0]->value)
		) {
			return true;
		} elseif ( // keep list for $list[array_search($needle, $list)] assignments
			$arrayDimFetch->dim instanceof Expr\FuncCall
			&& $arrayDimFetch->dim->name instanceof Name
			&& !$arrayDimFetch->dim->isFirstClassCallable()
			&& $arrayDimFetch->dim->name->toLowerString() === 'array_search'
			&& count($arrayDimFetch->dim->getArgs()) >= 1
			&& $this->isSameVariable($arrayDimFetch->var, $arrayDimFetch->dim->getArgs()[1]->value)
		) {
			return true;
		}

		return false;
	}

	private function isSameVariable(Expr $a, Expr $b): bool
	{
		if ($a instanceof Variable && $b instanceof Variable && is_string($a->name) && is_string($b->name)) {
			return $a->name === $b->name;
		}

		return false;
	}

}
