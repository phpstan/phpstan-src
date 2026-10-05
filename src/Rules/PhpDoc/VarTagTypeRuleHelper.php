<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\NameScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\PhpDoc\NameScopeAlreadyBeingCreatedException;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_map;
use function count;
use function is_string;
use function sprintf;

#[AutowiredService]
final class VarTagTypeRuleHelper
{

	public function __construct(
		private TypeNodeResolver $typeNodeResolver,
		private FileTypeMapper $fileTypeMapper,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter(ref: '%reportWrongPhpDocTypeInVarTag%')]
		private bool $checkTypeAgainstPhpDocType,
		#[AutowiredParameter(ref: '%reportAnyTypeWideningInVarTag%')]
		private bool $strictWideningCheck,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	/**
	 * @param VarTag[] $varTags
	 * @param string[] $assignedVariables
	 * @return list<IdentifierRuleError>
	 */
	public function checkVarType(Scope $scope, Node\Expr $var, Node\Expr $expr, array $varTags, array $assignedVariables): array
	{
		$errors = [];

		if ($var instanceof Expr\Variable && is_string($var->name)) {
			if (array_key_exists($var->name, $varTags)) {
				$varTagType = $varTags[$var->name]->getType();
			} elseif (count($assignedVariables) === 1 && array_key_exists(0, $varTags)) {
				$varTagType = $varTags[0]->getType();
			} else {
				return [];
			}

			return $this->checkExprType($scope, $expr, $varTagType);
		} elseif ($var instanceof Expr\List_ || $var instanceof Expr\Array_) {
			foreach ($var->items as $i => $arrayItem) {
				if ($arrayItem === null) {
					continue;
				}
				if ($arrayItem->key === null) {
					$dimExpr = new Node\Scalar\Int_($i);
				} else {
					$dimExpr = $arrayItem->key;
				}

				// carry both flavours so the native-type check reads the native
				// offset type, not the phpdoc one (mirrors the foreach key/value
				// sites in WrongVariableNameInVarTagRule)
				$itemErrors = $this->checkVarType($scope, $arrayItem->value, new NativeTypeExpr(
					$scope->getType($expr)->getOffsetValueType($scope->getType($dimExpr)),
					$scope->getNativeType($expr)->getOffsetValueType($scope->getNativeType($dimExpr)),
				), $varTags, $assignedVariables);
				foreach ($itemErrors as $error) {
					$errors[] = $error;
				}
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkExprType(Scope $scope, Node\Expr $expr, Type $varTagType): array
	{
		$errors = [];
		// a constant expression (a static variable's `= 1` default, a literal) is
		// position-independent and priced without asking the scope about a node
		// the walk stores only after the rule fires on its statement
		$constantType = $this->priceConstantExpr($scope, $expr);
		$exprNativeType = $constantType ?? $scope->getScopeNativeType($expr);
		$containsPhpStanType = $this->containsPhpStanType($varTagType);

		$isValidSuperTypeOfExpr = $this->isValidSuperTypeOfExpr($scope, $expr, $exprNativeType, $varTagType);
		if (!$isValidSuperTypeOfExpr->yes()) {
			$verbosity = VerbosityLevel::getRecommendedLevelByType($exprNativeType, $varTagType);
			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @var with type %s is not subtype of native type %s.',
				$varTagType->describe($verbosity),
				$exprNativeType->describe($verbosity),
			))->acceptsReasonsTip($isValidSuperTypeOfExpr->reasons)->identifier('varTag.nativeType')->build();
		} elseif ($this->checkTypeAgainstPhpDocType || $containsPhpStanType) {
			$exprType = $constantType ?? $scope->getScopeType($expr);
			$isValidSuperTypeOfExpr = $this->isValidSuperTypeOfExpr($scope, $expr, $exprType, $varTagType);
			if (!$isValidSuperTypeOfExpr->yes()) {
				$verbosity = VerbosityLevel::getRecommendedLevelByType($exprType, $varTagType);
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @var with type %s is not subtype of type %s.',
					$varTagType->describe($verbosity),
					$exprType->describe($verbosity),
				))->acceptsReasonsTip($isValidSuperTypeOfExpr->reasons)->identifier('varTag.type')->build();
			}
		}

		if ($containsPhpStanType && count($errors) === 0) {
			$exprType = $scope->getScopeType($expr);
			if (!$exprType->equals($varTagType)) {
				$verbosity = VerbosityLevel::getRecommendedLevelByType($exprType, $varTagType);
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @var assumes the expression with type %s is always %s but it\'s error-prone and dangerous.',
					$exprType->describe($verbosity),
					$varTagType->describe($verbosity),
				))->identifier('phpstanApi.varTagAssumption')->build();
			}
		}

		return $errors;
	}

	private function containsPhpStanType(Type $type): bool
	{
		$classReflections = TypeUtils::toBenevolentUnion($type)->getObjectClassReflections();
		if (!$this->reflectionProvider->hasClass(Type::class)) {
			return false;
		}

		$typeClass = $this->reflectionProvider->getClass(Type::class);
		foreach ($classReflections as $classReflection) {
			if (!$classReflection->isSubclassOfClass($typeClass)) {
				continue;
			}

			return true;
		}

		return false;
	}

	private function isValidSuperTypeOfExpr(Scope $scope, Node\Expr $expr, Type $type, Type $varTagType): IsSuperTypeOfResult
	{
		if ($expr instanceof Expr\Array_) {
			if ($expr->items === []) {
				$type = new ArrayType(new MixedType(), new MixedType());
			}

			return $this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
		}

		if ($expr instanceof Expr\ConstFetch) {
			return $this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
		}

		if ($expr instanceof Node\Scalar) {
			return $this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
		}

		if ($expr instanceof Expr\New_) {
			if ($type instanceof GenericObjectType) {
				$type = new ObjectType($type->getClassName());
			}
		}

		return $this->isValidSuperType($scope, $type, $varTagType);
	}

	private function isValidSuperType(Scope $scope, Type $type, Type $varTagType, int $depth = 0): IsSuperTypeOfResult
	{
		if ($this->strictWideningCheck) {
			return $this->isSuperTypeOfVarType($scope, $type, $varTagType);
		}

		$type = TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
			if ($type instanceof GenericObjectType) {
				$type = $type->changeVariances(array_map(
					static fn (TemplateTypeVariance $variance) => $variance->invariant() ? TemplateTypeVariance::createCovariant() : $variance,
					$type->getVariances(),
				));
			}

			return $traverse($type);
		});

		if ($type->isConstantArray()->yes()) {
			if ($type->isIterableAtLeastOnce()->no()) {
				$type = new ArrayType(new MixedType(), new MixedType());
				return $this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
			}
		}

		if ($type->isIterable()->yes() && $varTagType->isIterable()->yes()) {
			$isAtLeastMaybeSuperTypeOf = $this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
			if ($isAtLeastMaybeSuperTypeOf->no()) {
				return $isAtLeastMaybeSuperTypeOf;
			}

			$innerType = $type->getIterableValueType();
			$innerVarTagType = $varTagType->getIterableValueType();

			if ($type->equals($innerType) || $varTagType->equals($innerVarTagType)) {
				return $this->isSuperTypeOfVarType($scope, $innerType, $innerVarTagType);
			}

			return $this->isValidSuperType($scope, $innerType, $innerVarTagType, $depth + 1);
		}

		if ($depth === 0 && $type->isConstantValue()->yes()) {
			return $this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
		}

		return $this->isSuperTypeOfVarType($scope, $type, $varTagType);
	}

	private function isSuperTypeOfVarType(Scope $scope, Type $type, Type $varTagType): IsSuperTypeOfResult
	{
		if ($type->isSuperTypeOf($varTagType)->yes()) {
			return IsSuperTypeOfResult::createYes();
		}

		try {
			$type = $this->typeNodeResolver->resolve($type->toPhpDocNode(), $this->createNameScope($scope));
		} catch (NameScopeAlreadyBeingCreatedException) {
			return IsSuperTypeOfResult::createYes();
		}

		return $type->isSuperTypeOf($varTagType);
	}

	private function isAtLeastMaybeSuperTypeOfVarType(Scope $scope, Type $type, Type $varTagType): IsSuperTypeOfResult
	{
		if (!$type->isSuperTypeOf($varTagType)->no()) {
			return IsSuperTypeOfResult::createYes();
		}

		try {
			$type = $this->typeNodeResolver->resolve($type->toPhpDocNode(), $this->createNameScope($scope));
		} catch (NameScopeAlreadyBeingCreatedException) {
			return IsSuperTypeOfResult::createYes();
		}

		$isSuperTypeOf = $type->isSuperTypeOf($varTagType);
		if (!$isSuperTypeOf->no()) {
			return IsSuperTypeOfResult::createYes();
		}

		return $isSuperTypeOf;
	}

	/**
	 * @throws NameScopeAlreadyBeingCreatedException
	 */
	private function createNameScope(Scope $scope): NameScope
	{
		$function = $scope->getFunction();

		return $this->fileTypeMapper->getNameScope(
			$scope->getFile(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$function !== null ? $function->getName() : null,
		)->withoutNamespaceAndUses();
	}

	/**
	 * A constant expression (a static variable's `= 1` default, a literal) is
	 * position-independent and priced without asking the scope about a node the
	 * walk stores only after the rule fires on its statement. Null otherwise.
	 */
	private function priceConstantExpr(Scope $scope, Node\Expr $expr): ?Type
	{
		if (
			$expr instanceof Node\Scalar\Int_
			|| $expr instanceof Node\Scalar\String_
			|| $expr instanceof Node\Scalar\Float_
			|| $expr instanceof Node\Expr\ConstFetch
			|| ($expr instanceof Node\Expr\ClassConstFetch && $expr->class instanceof Node\Name && $expr->name instanceof Node\Identifier)
		) {
			return $this->initializerExprTypeResolver->getType($expr, InitializerExprContext::fromScope($scope));
		}

		return null;
	}

}
