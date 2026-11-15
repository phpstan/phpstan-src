<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\EnumCase>
 */
#[RegisteredRule(level: 0)]
final class EnumCaseAttributesRule implements Rule
{

	public function __construct(private AttributesCheck $attributesCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\EnumCase::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if (!$scope->getPhpVersion()->supportsOverrideAttributeOnClassConstant()->yes()) {
			foreach ($node->attrGroups as $attrGroup) {
				foreach ($attrGroup->attrs as $attr) {
					if ($attr->name->toLowerString() !== 'override') {
						continue;
					}

					return [
						RuleErrorBuilder::message('Attribute class Override can be used with enum cases only on PHP 8.6 and later.')
							->identifier('enum.caseOverrideAttribute')
							->nonIgnorable()
							->build(),
					];
				}
			}
		}

		return $this->attributesCheck->check(
			$scope,
			$node->attrGroups,
			Attribute::TARGET_CLASS_CONSTANT,
			'class constant',
		);
	}

}
