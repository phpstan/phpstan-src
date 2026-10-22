<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\NullsafePropertyFetchExpressionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<NullsafePropertyFetchExpressionNode>
 */
#[RegisteredRule(level: 4)]
final class NullsafePropertyFetchRule implements Rule
{

	public function __construct(
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
		#[AutowiredParameter(ref: '%tips.treatPhpDocTypesAsCertain%')]
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return NullsafePropertyFetchExpressionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalNode = $node->getOriginalNode();
		$calledOnType = $this->treatPhpDocTypesAsCertain ? $node->getCalledOnType() : $node->getCalledOnNativeType();
		if (!$calledOnType->isNull()->no()) {
			return [];
		}

		if ($scope->isUndefinedExpressionAllowed($originalNode)) {
			return [];
		}

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($node): RuleErrorBuilder {
			if (!$this->treatPhpDocTypesAsCertain || !$this->treatPhpDocTypesAsCertainTip) {
				return $ruleErrorBuilder;
			}

			$calledOnNativeType = $node->getCalledOnNativeType();
			if ($calledOnNativeType->isNull()->no()) {
				return $ruleErrorBuilder;
			}

			return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
		};

		$ruleErrorBuilder = $addTip(
			RuleErrorBuilder::message(sprintf(
				'Using nullsafe property access on non-nullable type %s. Use -> instead.',
				$calledOnType->describe(VerbosityLevel::typeOnly()),
			)),
		)
			->line($originalNode->name->getStartLine())
			->identifier('nullsafe.neverNull');

		return [$ruleErrorBuilder->build()];
	}

}
