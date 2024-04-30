<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
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
		private bool $reportAlwaysTrueInLastCondition,
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

			return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
		};

		if (!$isAlways) {
			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Call to function %s()%s will always evaluate to false.',
					$functionName,
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
				)))->identifier('function.impossibleType')->build(),
			];
		} elseif ($this->checkAlwaysTrueCheckTypeFunctionCall) {
			$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
			if ($isLast === true && !$this->reportAlwaysTrueInLastCondition) {
				return [];
			}

			$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
				'Call to function %s()%s will always evaluate to true.',
				$functionName,
				$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
			)));
			if ($isLast === false && !$this->reportAlwaysTrueInLastCondition) {
				$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
			}

			$errorBuilder->identifier('function.alreadyNarrowedType');

			return [$errorBuilder->build()];
		}

		return [];
	}

}
