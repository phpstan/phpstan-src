<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
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

class VarTagTypeRuleHelper
{

	public function __construct(private bool $checkTypeAgainstPhpDocType)
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
		$exprNativeType = $scope->getNativeType($expr);
		$containsPhpStanType = $this->containsPhpStanType($varTagType);
		if ($this->shouldVarTagTypeBeReported($expr, $exprNativeType, $varTagType)) {
			$verbosity = VerbosityLevel::getRecommendedLevelByType($exprNativeType, $varTagType);
			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @var with type %s is not subtype of native type %s.',
				$varTagType->describe($verbosity),
				$exprNativeType->describe($verbosity),
			))->identifier('varTag.nativeType')->build();
		} else {
			$exprType = $scope->getType($expr);
			if (
				$this->shouldVarTagTypeBeReported($expr, $exprType, $varTagType)
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
			$exprType = $scope->getType($expr);
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
		foreach ($classReflections as $classReflection) {
			if (!$classReflection->isSubclassOf(Type::class)) {
				continue;
			}

			return true;
		}

		return false;
	}

	private function shouldVarTagTypeBeReported(Node\Expr $expr, Type $type, Type $varTagType): bool
	{
		if ($expr instanceof Expr\Array_) {
			if ($expr->items === []) {
				$type = new ArrayType(new MixedType(), new MixedType());
			}

			return $type->isSuperTypeOf($varTagType)->no();
		}

		if ($expr instanceof Expr\ConstFetch) {
			return $type->isSuperTypeOf($varTagType)->no();
		}

		if ($expr instanceof Node\Scalar) {
			return $type->isSuperTypeOf($varTagType)->no();
		}

		if ($expr instanceof Expr\New_) {
			if ($type instanceof GenericObjectType) {
				$type = new ObjectType($type->getClassName());
			}
		}

		return $this->checkType($type, $varTagType);
	}

	private function checkType(Type $type, Type $varTagType, int $depth = 0): bool
	{
		if ($type->isConstantArray()->yes()) {
			if ($type->isIterableAtLeastOnce()->no()) {
				$type = new ArrayType(new MixedType(), new MixedType());
				return $type->isSuperTypeOf($varTagType)->no();
			}
		}

		if ($type->isIterable()->yes() && $varTagType->isIterable()->yes()) {
			if ($type->isSuperTypeOf($varTagType)->no()) {
				return true;
			}

			$innerType = $type->getIterableValueType();
			$innerVarTagType = $varTagType->getIterableValueType();

			if ($type->equals($innerType) || $varTagType->equals($innerVarTagType)) {
				return !$innerType->isSuperTypeOf($innerVarTagType)->yes();
			}

			return $this->checkType($innerType, $innerVarTagType, $depth + 1);
		}

		if ($type->isConstantValue()->yes() && $depth === 0) {
			return $type->isSuperTypeOf($varTagType)->no();
		}

		return !$type->isSuperTypeOf($varTagType)->yes();
	}

}
