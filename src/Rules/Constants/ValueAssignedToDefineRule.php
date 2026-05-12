<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<FuncCall>
 */
#[RegisteredRule(level: 2)]
final class ValueAssignedToDefineRule implements Rule
{

	public function __construct(private ConstantResolver $constantResolver)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (strtolower((string) $node->name) !== 'define') {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) < 2) {
			return [];
		}

		$constantNameStrings = $scope->getType($args[0]->value)->getConstantStrings();
		if (count($constantNameStrings) !== 1 || $constantNameStrings[0]->getValue() === '') {
			return [];
		}

		$constantName = $constantNameStrings[0]->getValue();
		$configuredType = $this->constantResolver->getExplicitGlobalConstantType($constantName);
		if ($configuredType === null) {
			return [];
		}

		$valueType = $scope->getType($args[1]->value);
		$accepts = $configuredType->accepts($valueType, true);
		if ($accepts->yes()) {
			return [];
		}

		$verbosity = VerbosityLevel::getRecommendedLevelByType($configuredType, $valueType);

		return [
			RuleErrorBuilder::message(sprintf(
				'Constant %s (%s) does not accept value %s.',
				$constantName,
				$configuredType->describe(VerbosityLevel::typeOnly()),
				$valueType->describe($verbosity),
			))->acceptsReasonsTip($accepts->reasons)->identifier('constant.value')->build(),
		];
	}

}
