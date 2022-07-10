<?php declare(strict_types=1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\LogicalXor;

/**
 * @implements \Rule<LogicalXor>
 */
class BooleanXorConstantConditionRule implements Rule
{
	public function __construct(
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return LogicalXor::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';

		$nodeType = $scope->getType($node);
		if ($nodeType instanceof ConstantBooleanType) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node, $tipText): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $scope->doNotTreatPhpDocTypesAsCertain()->getType($node);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip($tipText);
			};

			$messages[] = $addTip(RuleErrorBuilder::message(sprintf(
				'Result of xor is always %s.',
				$nodeType->getValue() ? 'true' : 'false',
			)))->build();
		}

		return $messages;
	}

}
