<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SuperglobalTypeResolver;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function in_array;
use function is_string;
use function sprintf;

/**
 * Reports a write that leaves a superglobal outside the type it is held to by
 * `readonlySuperglobals` or `superglobalTypes`. Once the scope forgets the
 * written value, such as after a function call, reads fall back to that type,
 * so a value outside it would be typed wrongly from then on.
 *
 * @implements Rule<VariableAssignNode>
 */
#[RegisteredRule(level: 3)]
final class SuperglobalAssignTypeRule implements Rule
{

	public function __construct(
		private SuperglobalTypeResolver $superglobalTypeResolver,
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return VariableAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$variable = $node->getVariable();
		if (!is_string($variable->name) || !in_array($variable->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
			return [];
		}

		$superglobalType = $this->superglobalTypeResolver->getContractType($variable->name);
		if ($superglobalType === null) {
			return [];
		}

		$assignedValueType = $scope->getType($node->getAssignedExpr());

		// what the superglobal held before this write was either accepted or already reported,
		// so only a value this write newly brings in is reported, not every later write
		$acceptingType = TypeCombinator::union($superglobalType, $scope->getType(new Variable($variable->name)));
		$accepts = $this->ruleLevelHelper->accepts($acceptingType, $assignedValueType, $scope->isDeclareStrictTypes());
		if ($accepts->result) {
			return [];
		}

		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($superglobalType, $assignedValueType);

		return [
			RuleErrorBuilder::message(sprintf(
				'Superglobal $%s (%s) does not accept %s.',
				$variable->name,
				$superglobalType->describe($verbosityLevel),
				$assignedValueType->describe($verbosityLevel),
			))
				->identifier('assign.superglobalType')
				->acceptsReasonsTip($accepts->reasons)
				->build(),
		];
	}

}
