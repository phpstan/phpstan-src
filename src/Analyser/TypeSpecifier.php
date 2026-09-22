<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ExtensionClassHelper;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;
use function is_array;

#[AutowiredService(name: 'typeSpecifier', factory: '@typeSpecifierFactory::create')]
final class TypeSpecifier
{

	private const CONTAINS_CALL_ATTRIBUTE_NAME = 'containsCall';

	/** @var MethodTypeSpecifyingExtension[][]|null */
	private ?array $methodTypeSpecifyingExtensionsByClass = null;

	/** @var StaticMethodTypeSpecifyingExtension[][]|null */
	private ?array $staticMethodTypeSpecifyingExtensionsByClass = null;

	/**
	 * @param FunctionTypeSpecifyingExtension[] $functionTypeSpecifyingExtensions
	 * @param MethodTypeSpecifyingExtension[] $methodTypeSpecifyingExtensions
	 * @param StaticMethodTypeSpecifyingExtension[] $staticMethodTypeSpecifyingExtensions
	 */
	public function __construct(
		private ExprPrinter $exprPrinter,
		private ReflectionProvider $reflectionProvider,
		private array $functionTypeSpecifyingExtensions,
		private array $methodTypeSpecifyingExtensions,
		private array $staticMethodTypeSpecifyingExtensions,
		private bool $rememberPossiblyImpureFunctionValues,
		private Container $container,
	)
	{
	}

	/**
	 * @api
	 */
	public function specifyTypesInCondition(
		Scope $scope,
		Expr $expr,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		if ($expr instanceof Expr\CallLike && $expr->isFirstClassCallable()) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		$exprHandler = ExprHandlerRegistry::resolve($expr, $this->container);
		if ($exprHandler !== null) {
			if ($scope instanceof MutatingScope) {
				return $scope->specifyTypesOfNewWorldHandlerNode($expr, $context);
			}
		}

		return $this->specifyDefaultTypes($scope, $expr, $context);
	}

	/**
	 * Fallback used by ExprHandler::specifyTypes implementations that have no
	 * Expr-specific narrowing: applies the default truthy/falsey narrowing, or
	 * returns empty SpecifiedTypes in a null context.
	 *
	 * @internal
	 */
	public function specifyDefaultTypes(Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$context->null()) {
			return $this->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

	/** @internal */
	public function handleDefaultTruthyOrFalseyContext(TypeSpecifierContext $context, Expr $expr, Scope $scope): SpecifiedTypes
	{
		if ($context->null()) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}
		if (!$context->truthy()) {
			$type = StaticTypeFactory::truthy();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse(), $scope)->setRootExpr($expr);
		} elseif (!$context->falsey()) {
			$type = StaticTypeFactory::falsey();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse(), $scope)->setRootExpr($expr);
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

	/**
	 * @api
	 */
	public function create(
		Expr $expr,
		Type $type,
		TypeSpecifierContext $context,
		Scope $scope,
	): SpecifiedTypes
	{
		if ($expr instanceof Instanceof_ || $expr instanceof Expr\List_) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		$specifiedExprs = [];
		if ($expr instanceof Expr\Assign) {
			$specifiedExprs[] = $expr->var;
			$specifiedExprs[] = $expr->expr;

			while ($expr->expr instanceof Expr\Assign) {
				$specifiedExprs[] = $expr->expr->var;
				$expr = $expr->expr;
			}
		} elseif ($expr instanceof Expr\AssignOp\Coalesce) {
			$specifiedExprs[] = $expr->var;
		} else {
			$specifiedExprs[] = $expr;
		}

		$types = null;

		foreach ($specifiedExprs as $specifiedExpr) {
			$newTypes = $this->createForExpr($specifiedExpr, $type, $context, $scope);

			if ($types === null) {
				$types = $newTypes;
			} else {
				$types = $types->unionWith($newTypes);
			}
		}

		return $types;
	}

	private function createForExpr(
		Expr $expr,
		Type $type,
		TypeSpecifierContext $context,
		Scope $scope,
	): SpecifiedTypes
	{
		// the null-containment probe only feeds the nullsafe-shortcircuit unwrap
		// and createNullsafeTypes() - both are no-ops for a bare variable, so the
		// probe (and its type ask) is skipped for one
		if (!$expr instanceof Expr\Variable) {
			if ($context->true()) {
				$containsNull = !$type->isNull()->no() && !$scope->getType($expr)->isNull()->no();
			} elseif ($context->false()) {
				$containsNull = !TypeCombinator::containsNull($type) && !$scope->getType($expr)->isNull()->no();
			}
		}

		$originalExpr = $expr;
		if (isset($containsNull) && !$containsNull) {
			$expr = NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($expr);
		}

		if (
			!$context->null()
			&& $expr instanceof Expr\BinaryOp\Coalesce
		) {
			if (
				($context->true() && $type->isSuperTypeOf($scope->getType($expr->right))->no())
				|| ($context->false() && $type->isSuperTypeOf($scope->getType($expr->right))->yes())
			) {
				$expr = $expr->left;
			}
		}

		if (
			$expr instanceof FuncCall
			&& $expr->name instanceof Name
			&& !$this->reflectionProvider->hasFunction($expr->name, $scope)
		) {
			return new SpecifiedTypes([], []);
		}

		if (!($expr instanceof AlwaysRememberedExpr) && $this->expressionContainsNonPureCall($expr, $scope)) {
			if (isset($containsNull) && !$containsNull) {
				return $this->createNullsafeTypes($originalExpr, $scope, $context, $type);
			}

			return new SpecifiedTypes([], []);
		}

		$sureTypes = [];
		$sureNotTypes = [];
		if ($context->false()) {
			$exprString = $this->exprPrinter->printExpr($expr);
			$sureNotTypes[$exprString] = [$expr, $type];

			if ($expr !== $originalExpr) {
				$originalExprString = $this->exprPrinter->printExpr($originalExpr);
				$sureNotTypes[$originalExprString] = [$originalExpr, $type];
			}
		} elseif ($context->true()) {
			$exprString = $this->exprPrinter->printExpr($expr);
			$sureTypes[$exprString] = [$expr, $type];

			if ($expr !== $originalExpr) {
				$originalExprString = $this->exprPrinter->printExpr($originalExpr);
				$sureTypes[$originalExprString] = [$originalExpr, $type];
			}
		}

		$types = new SpecifiedTypes($sureTypes, $sureNotTypes);
		if (isset($containsNull) && !$containsNull) {
			return $this->createNullsafeTypes($originalExpr, $scope, $context, $type)->unionWith($types);
		}

		return $types;
	}

	private function expressionContainsNonPureCall(Expr $expr, Scope $scope): bool
	{
		// The answer for an expression without any call in it cannot change between
		// scopes, and most specified expressions (plain variables, property fetches,
		// constant fetches) are of that shape, so it's remembered on the node itself.
		if ($expr->getAttribute(self::CONTAINS_CALL_ATTRIBUTE_NAME) === false) {
			return false;
		}

		$containsCall = false;
		$containsNonPureCall = $this->findNonPureCall($expr, $scope, $containsCall);
		if (!$containsCall) {
			$expr->setAttribute(self::CONTAINS_CALL_ATTRIBUTE_NAME, false);
		}

		return $containsNonPureCall;
	}

	/**
	 * Depth-first pre-order search for a call that isn't known to be pure, replacing a
	 * NodeFinder::findFirst() call - this runs for every expression being specified,
	 * so the traverser/visitor machinery overhead was significant.
	 *
	 * $containsCall is set when the sub-tree contains a call of any kind.
	 */
	private function findNonPureCall(Node $node, Scope $scope, bool &$containsCall): bool
	{
		if ($node instanceof Expr\CallLike) {
			$containsCall = true;

			if ($this->callIsNotPure($node, $scope)) {
				return true;
			}
		}

		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->$subNodeName;
			if ($subNode instanceof Node) {
				if ($this->findNonPureCall($subNode, $scope, $containsCall)) {
					return true;
				}
			} elseif (is_array($subNode)) {
				foreach ($subNode as $subNodeItem) {
					if (
						$subNodeItem instanceof Node
						&& $this->findNonPureCall($subNodeItem, $scope, $containsCall)
					) {
						return true;
					}
				}
			}
		}

		return false;
	}

	private function callIsNotPure(Expr\CallLike $call, Scope $scope): bool
	{
		if ($call instanceof FuncCall) {
			if ($call->name instanceof Name) {
				if (!$this->reflectionProvider->hasFunction($call->name, $scope)) {
					return false;
				}

				return $this->isNotPure($this->reflectionProvider->getFunction($call->name, $scope)->hasSideEffects());
			}

			$nameType = $scope->getType($call->name);
			if ($nameType->isCallable()->yes()) {
				$isPure = null;
				foreach ($nameType->getCallableParametersAcceptors($scope) as $variant) {
					$variantIsPure = $variant->isPure();
					$isPure = $isPure === null ? $variantIsPure : $isPure->and($variantIsPure);
				}
				if ($isPure !== null) {
					return $this->isNotPure($isPure->negate());
				}
			}

			return false;
		}

		if ($call instanceof MethodCall) {
			if (!$call->name instanceof Identifier) {
				return true;
			}

			$methodReflection = $scope->getMethodReflection($scope->getType($call->var), $call->name->name);
			if ($methodReflection === null) {
				return true;
			}

			return $this->isNotPure($methodReflection->hasSideEffects());
		}

		if ($call instanceof StaticCall) {
			if (!$call->name instanceof Identifier) {
				return true;
			}

			if ($call->class instanceof Name) {
				$calledOnType = $scope->resolveTypeByName($call->class);
			} else {
				$calledOnType = $scope->getType($call->class);
			}

			$methodReflection = $scope->getMethodReflection($calledOnType, $call->name->name);
			if ($methodReflection === null) {
				return true;
			}

			return $this->isNotPure($methodReflection->hasSideEffects());
		}

		return false;
	}

	private function isNotPure(TrinaryLogic $hasSideEffects): bool
	{
		if ($hasSideEffects->yes()) {
			return true;
		}

		return !$this->rememberPossiblyImpureFunctionValues && !$hasSideEffects->no();
	}

	private function createNullsafeTypes(Expr $expr, Scope $scope, TypeSpecifierContext $context, ?Type $type): SpecifiedTypes
	{
		if ($expr instanceof Expr\NullsafePropertyFetch) {
			if ($type !== null) {
				$propertyFetchTypes = $this->create(new PropertyFetch($expr->var, $expr->name), $type, $context, $scope);
			} else {
				$propertyFetchTypes = $this->create(new PropertyFetch($expr->var, $expr->name), new NullType(), TypeSpecifierContext::createFalse(), $scope);
			}

			return $propertyFetchTypes->unionWith(
				$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse(), $scope),
			);
		}

		if ($expr instanceof Expr\NullsafeMethodCall) {
			if ($type !== null) {
				$methodCallTypes = $this->create(new MethodCall($expr->var, $expr->name, $expr->args), $type, $context, $scope);
			} else {
				$methodCallTypes = $this->create(new MethodCall($expr->var, $expr->name, $expr->args), new NullType(), TypeSpecifierContext::createFalse(), $scope);
			}

			return $methodCallTypes->unionWith(
				$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse(), $scope),
			);
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->createNullsafeTypes($expr->var, $scope, $context, null);
		}

		if ($expr instanceof Expr\MethodCall) {
			return $this->createNullsafeTypes($expr->var, $scope, $context, null);
		}

		if ($expr instanceof Expr\ArrayDimFetch) {
			return $this->createNullsafeTypes($expr->var, $scope, $context, null);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->createNullsafeTypes($expr->class, $scope, $context, null);
		}

		if ($expr instanceof Expr\StaticCall && $expr->class instanceof Expr) {
			return $this->createNullsafeTypes($expr->class, $scope, $context, null);
		}

		return new SpecifiedTypes([], []);
	}

	/**
	 * @return FunctionTypeSpecifyingExtension[]
	 *
	 * @internal
	 */
	public function getFunctionTypeSpecifyingExtensions(): array
	{
		return $this->functionTypeSpecifyingExtensions;
	}

	/**
	 * @return MethodTypeSpecifyingExtension[]
	 *
	 * @internal
	 */
	public function getMethodTypeSpecifyingExtensionsForClass(string $className): array
	{
		if ($this->methodTypeSpecifyingExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->methodTypeSpecifyingExtensions as $extension) {
				$byClass[$extension->getClass()][] = $extension;
			}

			$this->methodTypeSpecifyingExtensionsByClass = $byClass;
		}
		return $this->getTypeSpecifyingExtensionsForType($this->methodTypeSpecifyingExtensionsByClass, $className);
	}

	/**
	 * @return StaticMethodTypeSpecifyingExtension[]
	 *
	 * @internal
	 */
	public function getStaticMethodTypeSpecifyingExtensionsForClass(string $className): array
	{
		if ($this->staticMethodTypeSpecifyingExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->staticMethodTypeSpecifyingExtensions as $extension) {
				$byClass[$extension->getClass()][] = $extension;
			}

			$this->staticMethodTypeSpecifyingExtensionsByClass = $byClass;
		}
		return $this->getTypeSpecifyingExtensionsForType($this->staticMethodTypeSpecifyingExtensionsByClass, $className);
	}

	/**
	 * @param MethodTypeSpecifyingExtension[][]|StaticMethodTypeSpecifyingExtension[][] $extensions
	 * @return mixed[]
	 */
	private function getTypeSpecifyingExtensionsForType(array $extensions, string $className): array
	{
		$extensionsForClass = [[]];
		$extensionClassNames = ExtensionClassHelper::getExtensionClassNames($this->reflectionProvider, $className);
		foreach ($extensionClassNames as $extensionClassName) {
			if (!isset($extensions[$extensionClassName])) {
				continue;
			}

			$extensionsForClass[] = $extensions[$extensionClassName];
		}

		return array_merge(...$extensionsForClass);
	}

}
