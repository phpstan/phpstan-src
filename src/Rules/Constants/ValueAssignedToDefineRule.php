<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<FuncCall>
 */
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
		if (count($constantNameStrings) === 0) {
			return [];
		}

		$valueType = $scope->getType($args[1]->value);
		$errors = [];

		foreach ($constantNameStrings as $constantNameString) {
			$constantName = $constantNameString->getValue();
			if ($constantName === '') {
				continue;
			}

			$configuredType = $this->constantResolver->getExplicitGlobalConstantType($constantName);
			if ($configuredType === null) {
				continue;
			}

			$accepts = $configuredType->accepts($valueType, true);
			if ($accepts->yes()) {
				continue;
			}

			$verbosity = VerbosityLevel::getRecommendedLevelByType($configuredType, $valueType);

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Configuration defined type for constant %s (%s) is incompatible with value %s.',
				$constantName,
				$configuredType->describe(VerbosityLevel::typeOnly()),
				$valueType->describe($verbosity),
			))->acceptsReasonsTip($accepts->reasons)->identifier('constant.defineValue')->build();
		}

		return $errors;
	}

}
