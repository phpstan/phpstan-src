<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

trait NanToStringHelperTrait
{

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkNanToString(NonFiniteFloatHelper $helper, Scope $scope, Expr $expr, string $where): array
	{
		if (!$scope->getPhpVersion()->warnsOnNonFiniteFloatConversion()->yes()) {
			return [];
		}

		$type = $scope->getType($expr);
		$canBe = $helper->canBeNan($type);
		if (!$helper->shouldReport($canBe)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'%s converts %s to string, which emits a warning%s.',
				$where,
				$type->describe(VerbosityLevel::value()),
				$canBe->yes() ? '' : ' when it is NAN',
			))->identifier('nonFiniteFloat.toString')->build(),
		];
	}

}
