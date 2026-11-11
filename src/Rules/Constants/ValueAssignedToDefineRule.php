<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
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
#[Conditional(
	attribute: new RegisteredRule(level: 2),
	if: '%featureToggles.checkDynamicConstantNameValues%',
)]
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

		$constantNameArg = ArgumentsNormalizer::getArgByPositionOrName($args, 0, 'constant_name');
		$valueArg = ArgumentsNormalizer::getArgByPositionOrName($args, 1, 'value');
		if ($constantNameArg === null || $valueArg === null) {
			return [];
		}

		$constantNameStrings = $scope->getType($constantNameArg->value)->getConstantStrings();
		if (count($constantNameStrings) === 0) {
			return [];
		}

		$valueType = $scope->getType($valueArg->value);
		$errors = [];

		foreach ($constantNameStrings as $constantNameString) {
			$constantName = $constantNameString->getValue();
			if ($constantName === '') {
				continue;
			}

			$configuredType = $this->constantResolver->getConfiguredGlobalConstantType($constantName);
			if ($configuredType === null) {
				continue;
			}

			$accepts = $configuredType->accepts($valueType, true);
			if ($accepts->yes()) {
				continue;
			}

			$verbosity = VerbosityLevel::getRecommendedLevelByType($configuredType, $valueType);

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Configuration defined type for constant %s (%s) does not accept value %s.',
				$constantName,
				$configuredType->describe(VerbosityLevel::value()),
				$valueType->describe($verbosity),
			))->acceptsReasonsTip($accepts->reasons)->identifier('constant.defineValue')->build();
		}

		return $errors;
	}

}
