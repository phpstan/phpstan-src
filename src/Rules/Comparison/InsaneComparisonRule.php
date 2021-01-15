<?php

declare(strict_types=1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;

/**
 * @see https://github.com/orklah/psalm-insane-comparison
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\BinaryOp>
 */
class InsaneComparisonRule implements \PHPStan\Rules\Rule
{
	private bool $treatMixedAsPossibleString;

	public function __construct(bool $treatMixedAsPossibleString)
	{
		$this->treatMixedAsPossibleString = $treatMixedAsPossibleString;
	}

	public function getNodeType(): string
	{
		return Node\Expr\BinaryOp::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Expr\BinaryOp\Equal
			&& !$node instanceof Node\Expr\BinaryOp\NotEqual
		) {
			return [];
		}

		$left_type = $scope->getType($node->left);
		$right_type = $scope->getType($node->right);

		// if ($left_type instanceof ErrorType || $right_type instanceof ErrorType) {
		//     return [];
		// }

		//on one hand, we're searching for literal 0
		$literal_0 = new ConstantIntegerType(0);
		$left_contain_0 = false;
		$right_contain_0 = false;

		if (!$literal_0->isSuperTypeOf($left_type)->no()) {
			if (!$this->treatMixedAsPossibleString || !($left_type instanceof MixedType)) {
				//Left type may contain 0
				$int_operand = $left_type;
				$other_operand = $right_type;
				$left_contain_0 = true;
			}
		}

		if (!$literal_0->isSuperTypeOf($right_type)->no()) {
			if (!$this->treatMixedAsPossibleString || !($right_type instanceof MixedType)) {
				//Right type may contain 0
				$int_operand = $right_type;
				$other_operand = $left_type;
				$right_contain_0 = true;
			}
		}

		if (!isset($other_operand, $int_operand)) {
			return [];
		}
		if (!$left_contain_0 && !$right_contain_0) {
			// Not interested
			return [];
		}
		if ($left_contain_0 && $right_contain_0) {
			//This is pretty inconclusive
			return [];
		}

		//On the other hand, we're searching for any non-numeric non-empty string
		if ((new StringType())->isSuperTypeOf($other_operand)->no()) {
			if (!$this->treatMixedAsPossibleString || !($other_operand instanceof MixedType)) {
				//we can stop here, there's no string in here
				return [];
			}
		}

		$string_operand = $other_operand;

		$eligible_int = null;
		foreach (TypeUtils::flattenTypes($int_operand) as $possibly_int) {
			if ($possibly_int instanceof ConstantIntegerType && $possibly_int->getValue() === 0) {
				$eligible_int = $possibly_int;
				break;
			}
			if ($possibly_int instanceof IntegerRangeType) {
				if ($possibly_int->isSuperTypeOf(new ConstantIntegerType(0))->no()) {
					// range doesn't contain 0, not interested
					continue;
				}
			} elseif ($possibly_int instanceof IntegerType) {
				// we found a general Int, it may contain 0
				$eligible_int = $possibly_int;
				break;
			}
		}

		$eligible_string = null;
		foreach (TypeUtils::flattenTypes($string_operand) as $possibly_string) {
			if ($possibly_string instanceof ConstantStringType) {
				if (!is_numeric($possibly_string->getValue())) {
					$eligible_string = $possibly_string;
					break;
				}
				continue;
			}
			if ($possibly_string instanceof AccessoryNumericStringType) {
				// not interested
				continue;
			}
			if ($possibly_string instanceof StringType && !$possibly_string instanceof ClassStringType) {
				$eligible_string = $possibly_string;
				break;
			}
			if ($this->treatMixedAsPossibleString && $possibly_string instanceof MixedType) {
				$eligible_string = $possibly_string;
				break;
			}
		}

		if ($eligible_int !== null && $eligible_string !== null) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Possible Insane Comparison between %s and %s',
					$scope->getType($node->left)->describe(VerbosityLevel::value()),
					$scope->getType($node->right)->describe(VerbosityLevel::value())
				))->line($node->left->getLine())->build(),
			];
		}

		return [];
	}
}
