<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class ImpossibleCheckTypeFunctionCallRule implements Rule
{

	public function __construct(
		private ImpossibleCheckTypeHelper $impossibleCheckTypeHelper,
		private bool $checkAlwaysTrueCheckTypeFunctionCall,
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		$functionName = (string) $node->name;
		if (strtolower($functionName) === 'is_a') {
			return [];
		}
		$isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $node);
		if ($isAlways === null) {
			return [];
		}

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
			if (!$this->treatPhpDocTypesAsCertain) {
				return $ruleErrorBuilder;
			}

			$isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $node);
			if ($isAlways !== null) {
				return $ruleErrorBuilder;
			}

			return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
		};

		if (!$isAlways) {
			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Call to function %s()%s will always evaluate to false.',
					$functionName,
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
				)))->build(),
			];
		} elseif ($this->checkAlwaysTrueCheckTypeFunctionCall) {
			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Call to function %s()%s will always evaluate to true.',
					$functionName,
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
				)))->build(),
			];
		}

		return [];
	}

}
