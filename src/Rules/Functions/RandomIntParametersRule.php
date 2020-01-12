<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
class RandomIntParametersRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof \PhpParser\Node\Name)) {
			return [];
		}

		if (strtolower((string) $node->name) !== 'random_int') {
			return [];
		}

		$minType = $scope->getType($node->args[0]->value)->toInteger();
		$maxType = $scope->getType($node->args[1]->value)->toInteger();

		if ($minType instanceof ConstantIntegerType
			&& $maxType instanceof ConstantIntegerType
			&& $minType->getValue() > $maxType->getValue()
		) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot call random_int() with $min parameter (%d) greater than $max parameter (%d).',
					$minType->getValue(),
					$maxType->getValue()
				))->build(),
			];
		}

		if ($minType instanceof IntegerRangeType
			&& $maxType instanceof ConstantIntegerType
			&& $minType->getMax() > $maxType->getValue()
		) {
			$message = $minType->getMin() > $maxType->getValue()
				? 'Cannot call random_int() with $min parameter (%s) greater than $max parameter (%d).'
				: 'Cannot call random_int() when $min parameter (%s) can be greater than $max parameter (%d).';

			return [
				RuleErrorBuilder::message(sprintf(
					$message,
					$minType->describe(VerbosityLevel::value()),
					$maxType->getValue()
				))->build(),
			];
		}

		if ($minType instanceof ConstantIntegerType
			&& $maxType instanceof IntegerRangeType
			&& $minType->getValue() > $maxType->getMin()
		) {
			$message = $minType->getValue() > $maxType->getMax()
				? 'Cannot call random_int() with $max parameter (%s) less than $min parameter (%d).'
				: 'Cannot call random_int() when $max parameter (%s) can be less than $min parameter (%d).';

			return [
				RuleErrorBuilder::message(sprintf(
					$message,
					$maxType->describe(VerbosityLevel::value()),
					$minType->getValue()
				))->build(),
			];
		}

		if ($minType instanceof IntegerRangeType
			&& $maxType instanceof IntegerRangeType
			&& $minType->getMax() > $maxType->getMin()
		) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot call random_int() with intersecting $min (%s) and $max (%s) parameters.',
					$minType->describe(VerbosityLevel::value()),
					$maxType->describe(VerbosityLevel::value())
				))->build(),
			];
		}

		return [];
	}

}
