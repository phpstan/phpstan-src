<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\NameScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\PhpDoc\NameScopeAlreadyBeingCreatedException;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
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

				$itemErrors = $this->checkVarType($scope, $arrayItem->value, new GetOffsetValueTypeExpr($expr, $dimExpr), $varTags, $assignedVariables);
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
		$exprNativeType = $scope->getScopeNativeType($expr);
		$containsPhpStanType = $this->containsPhpStanType($varTagType);
		if ($this->shouldVarTagTypeBeReported($scope, $expr, $exprNativeType, $varTagType)) {
			$verbosity = VerbosityLevel::getRecommendedLevelByType($exprNativeType, $varTagType);
			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @var with type %s is not subtype of native type %s.',
				$varTagType->describe($verbosity),
				$exprNativeType->describe($verbosity),
			))->identifier('varTag.nativeType')->build();
		} else {
			$exprType = $scope->getScopeType($expr);
			if (
				$this->shouldVarTagTypeBeReported($scope, $expr, $exprType, $varTagType)
				&& ($this->checkTypeAgainstPhpDocType || $containsPhpStanType)
			) {
				$verbosity = VerbosityLevel::getRecommendedLevelByType($exprType, $varTagType);
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @var with type %s is not subtype of type %s.',
					$varTagType->describe($verbosity),
					$exprType->describe($verbosity),
				))->identifier('varTag.type')->build();
			}
		}

		if (count($errors) === 0 && $containsPhpStanType) {
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

	private function shouldVarTagTypeBeReported(Scope $scope, Node\Expr $expr, Type $type, Type $varTagType): bool
	{
		if ($expr instanceof Expr\Array_) {
			if ($expr->items === []) {
				$type = new ArrayType(new MixedType(), new MixedType());
			}

			return !$this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
		}

		if ($expr instanceof Expr\ConstFetch) {
			return !$this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
		}

		if ($expr instanceof Node\Scalar) {
			return !$this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
		}

		if ($expr instanceof Expr\New_) {
			if ($type instanceof GenericObjectType) {
				$type = new ObjectType($type->getClassName());
			}
		}

		return $this->checkType($scope, $type, $varTagType);
	}

	private function checkType(Scope $scope, Type $type, Type $varTagType, int $depth = 0): bool
	{
		if ($this->strictWideningCheck) {
			return !$this->isSuperTypeOfVarType($scope, $type, $varTagType);
		}

		if ($type->isConstantArray()->yes()) {
			if ($type->isIterableAtLeastOnce()->no()) {
				$type = new ArrayType(new MixedType(), new MixedType());
				return !$this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
			}
		}

		if ($type->isIterable()->yes() && $varTagType->isIterable()->yes()) {
			if (!$this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType)) {
				return true;
			}

			$innerType = $type->getIterableValueType();
			$innerVarTagType = $varTagType->getIterableValueType();

			if ($type->equals($innerType) || $varTagType->equals($innerVarTagType)) {
				return !$this->isSuperTypeOfVarType($scope, $innerType, $innerVarTagType);
			}

			return $this->checkType($scope, $innerType, $innerVarTagType, $depth + 1);
		}

		if ($depth === 0 && $type->isConstantValue()->yes()) {
			return !$this->isAtLeastMaybeSuperTypeOfVarType($scope, $type, $varTagType);
		}

		return !$this->isSuperTypeOfVarType($scope, $type, $varTagType);
	}

	private function isSuperTypeOfVarType(Scope $scope, Type $type, Type $varTagType): bool
	{
		if ($type->isSuperTypeOf($varTagType)->yes()) {
			return true;
		}

		try {
			$type = $this->typeNodeResolver->resolve($type->toPhpDocNode(), $this->createNameScope($scope));
		} catch (NameScopeAlreadyBeingCreatedException) {
			return true;
		}

		return $type->isSuperTypeOf($varTagType)->yes();
	}

	private function isAtLeastMaybeSuperTypeOfVarType(Scope $scope, Type $type, Type $varTagType): bool
	{
		if (!$type->isSuperTypeOf($varTagType)->no()) {
			return true;
		}

		try {
			$type = $this->typeNodeResolver->resolve($type->toPhpDocNode(), $this->createNameScope($scope));
		} catch (NameScopeAlreadyBeingCreatedException) {
			return true;
		}

		return !$type->isSuperTypeOf($varTagType)->no();
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

}
