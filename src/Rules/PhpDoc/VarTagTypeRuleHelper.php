<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ConstantType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
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
	 * @return RuleError[]
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
					$dimExpr = new Node\Scalar\LNumber($i);
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
	 * @return RuleError[]
	 */
	public function checkExprType(Scope $scope, Node\Expr $expr, Type $varTagType): array
	{
		$errors = [];
		$exprNativeType = $scope->getNativeType($expr);
		if ($this->shouldVarTagTypeBeReported($expr, $exprNativeType, $varTagType)) {
			$verbosity = VerbosityLevel::getRecommendedLevelByType($exprNativeType, $varTagType);
			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @var with type %s is not subtype of native type %s.',
				$varTagType->describe($verbosity),
				$exprNativeType->describe($verbosity),
			))->build();
		} elseif ($this->checkTypeAgainstPhpDocType) {
			$exprType = $scope->getType($expr);
			if ($this->shouldVarTagTypeBeReported($expr, $exprType, $varTagType)) {
				$verbosity = VerbosityLevel::getRecommendedLevelByType($exprType, $varTagType);
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @var with type %s is not subtype of type %s.',
					$varTagType->describe($verbosity),
					$exprType->describe($verbosity),
				))->build();
			}
		}

		return $errors;
	}

	private function shouldVarTagTypeBeReported(Node\Expr $expr, Type $type, Type $varTagType): bool
	{
		if ($expr instanceof Expr\New_) {
			if ($type instanceof GenericObjectType) {
				$type = new ObjectType($type->getClassName());
			}
		}

		if ($type instanceof ConstantType) {
			return $type->isSuperTypeOf($varTagType)->no();
		}

		if ($type->isIterable()->yes() && $varTagType->isIterable()->yes()) {
			if ($type->isSuperTypeOf($varTagType)->no()) {
				return true;
			}

			return !$type->getIterableValueType()->isSuperTypeOf($varTagType->getIterableValueType())->yes();
		}

		return !$type->isSuperTypeOf($varTagType)->yes();
	}

}
