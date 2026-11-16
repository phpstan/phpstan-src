<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

trait NonFiniteFloatToIntTrait
{

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkToInt(NonFiniteFloatHelper $helper, Scope $scope, Expr $expr, string $where): array
	{
		if (!$scope->getPhpVersion()->warnsOnNonFiniteFloatConversion()->yes()) {
			return [];
		}

		$type = $scope->getType($expr);
		$canBe = $helper->canBeOutOfIntRange($type);
		if (!$helper->shouldReport($canBe)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'%s converts %s to int, which emits a warning%s.',
				$where,
				$type->describe(VerbosityLevel::value()),
				$canBe->yes() ? '' : ' when it is NAN, INF or out of the int range',
			))->identifier('nonFiniteFloat.toInt')->build(),
		];
	}

}
