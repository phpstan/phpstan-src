<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[RegisteredRule(level: 4)]
final class ImpossibleCheckTypeFunctionCallRule implements Rule
{

	public function __construct(
		private ImpossibleCheckTypeHelper $impossibleCheckTypeHelper,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
		#[AutowiredParameter]
		private bool $reportAlwaysTrueInLastCondition,
		#[AutowiredParameter(ref: '%tips.treatPhpDocTypesAsCertain%')]
		private bool $treatPhpDocTypesAsCertainTip,
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
			if (!$this->treatPhpDocTypesAsCertainTip) {
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
		}

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

}
