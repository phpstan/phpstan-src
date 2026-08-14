<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ExtensionClassHelper;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

#[AutowiredService(name: 'typeSpecifier', factory: '@typeSpecifierFactory::create')]
final class TypeSpecifier
{

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
		) {
			$has = $this->reflectionProvider->hasFunction($expr->name, $scope);
			if (!$has) {
				// backwards compatibility with previous behaviour
				return new SpecifiedTypes([], []);
			}

			$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
			$hasSideEffects = $functionReflection->hasSideEffects();
			if ($hasSideEffects->yes()) {
				return new SpecifiedTypes([], []);
			}

			if (!$this->rememberPossiblyImpureFunctionValues && !$hasSideEffects->no()) {
				return new SpecifiedTypes([], []);
			}
		}

		if (
			$expr instanceof FuncCall
			&& !$expr->name instanceof Name
		) {
			$nameType = $scope->getType($expr->name);
			if ($nameType->isCallable()->yes()) {
				$isPure = null;
				foreach ($nameType->getCallableParametersAcceptors($scope) as $variant) {
					$variantIsPure = $variant->isPure();
					$isPure = $isPure === null ? $variantIsPure : $isPure->and($variantIsPure);
				}

				if ($isPure !== null) {
					if ($isPure->no()) {
						return new SpecifiedTypes([], []);
					}

					if (!$this->rememberPossiblyImpureFunctionValues && !$isPure->yes()) {
						return new SpecifiedTypes([], []);
					}
				}
			}
		}

		if (
			$expr instanceof MethodCall
			&& $expr->name instanceof Node\Identifier
		) {
			$methodName = $expr->name->toString();
			$calledOnType = $scope->getType($expr->var);
			$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
			if (
				$methodReflection === null
				|| $methodReflection->hasSideEffects()->yes()
				|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			) {
				if (isset($containsNull) && !$containsNull) {
					return $this->createNullsafeTypes($originalExpr, $scope, $context, $type);
				}

				return new SpecifiedTypes([], []);
			}
		}

		if (
			$expr instanceof StaticCall
			&& $expr->name instanceof Node\Identifier
		) {
			$methodName = $expr->name->toString();
			if ($expr->class instanceof Name) {
				$calledOnType = $scope->resolveTypeByName($expr->class);
			} else {
				$calledOnType = $scope->getType($expr->class);
			}

			$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
			if (
				$methodReflection === null
				|| $methodReflection->hasSideEffects()->yes()
				|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			) {
				if (isset($containsNull) && !$containsNull) {
					return $this->createNullsafeTypes($originalExpr, $scope, $context, $type);
				}

				return new SpecifiedTypes([], []);
			}
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
