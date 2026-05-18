<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Const_>
 */
final class ValueAssignedToGlobalConstantRule implements Rule
{

	public function __construct(private ConstantResolver $constantResolver)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Const_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];

		foreach ($node->consts as $const) {
			if ($const->namespacedName !== null) {
				$constantName = $const->namespacedName->toString();
			} else {
				$constantName = $const->name->toString();
			}

			$configuredType = $this->constantResolver->getConfiguredGlobalConstantType($constantName);
			if ($configuredType === null) {
				continue;
			}

			$valueType = $scope->getType($const->value);
			$accepts = $configuredType->accepts($valueType, true);
			if ($accepts->yes()) {
				continue;
			}

			$verbosity = VerbosityLevel::getRecommendedLevelByType($configuredType, $valueType);

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Configuration defined type for constant %s (%s) does not accept value %s.',
				$constantName,
				$configuredType->describe(VerbosityLevel::precise()),
				$valueType->describe($verbosity),
			))->acceptsReasonsTip($accepts->reasons)->identifier('constant.value')->build();
		}

		return $errors;
	}

}
