<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

#[AutowiredService]
final class PossiblyImpureTipHelper
{

	public function __construct(
		#[AutowiredParameter(ref: '%tips.possiblyImpure%')]
		private bool $possiblyImpureTip,
	)
	{
	}

	/**
	 * @template T of RuleError
	 * @param RuleErrorBuilder<T> $ruleErrorBuilder
	 * @return RuleErrorBuilder<T>
	 */
	public function addTip(
		Scope $scope,
		Expr $conditionExpr,
		RuleErrorBuilder $ruleErrorBuilder,
	): RuleErrorBuilder
	{
		if (!$this->possiblyImpureTip) {
			return $ruleErrorBuilder;
		}
		if (!$scope instanceof MutatingScope) {
			return $ruleErrorBuilder;
		}
		$descriptions = $scope->findPossiblyImpureCallDescriptions($conditionExpr);
		if (count($descriptions) === 0) {
			return $ruleErrorBuilder;
		}

		return $ruleErrorBuilder->possiblyImpureTip($descriptions);
	}

}
