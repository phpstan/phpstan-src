<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use const PHP_VERSION_ID;

/**
 * @implements Rule<Node\Stmt\Const_>
 */
#[RegisteredRule(level: 0)]
final class ConstantAttributesRule implements Rule
{

	public function __construct(
		private AttributesCheck $attributesCheck,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Const_::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if ($node->attrGroups === []) {
			return [];
		}

		if (!$this->phpVersion->supportsAttributesOnGlobalConstants()) {
			return [
				RuleErrorBuilder::message('Attributes on global constants are supported only on PHP 8.5 and later.')
					->identifier('constant.attributesNotSupported')
					->nonIgnorable()
					->build(),
			];
		}

		if (PHP_VERSION_ID < 80500) {
			// because of Attribute::TARGET_CONSTANT constant
			return [
				RuleErrorBuilder::message('ConstantAttributesRule requires PHP 8.5 runtime to check the code.')
					->identifier('constant.attributesRuleCannotRun')
					->nonIgnorable()
					->build(),
			];
		}

		return $this->attributesCheck->check(
			$scope,
			$node->attrGroups,
			Attribute::TARGET_CONSTANT,
			'constant',
		);
	}

}
