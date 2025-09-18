<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InPropertyHookNode>
 */
#[RegisteredRule(level: 0)]
final class PropertyHookAttributesRule implements Rule
{

	public function __construct(private AttributesCheck $attributesCheck)
	{
	}

	public function getNodeType(): string
	{
		return InPropertyHookNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$attrGroups = $node->getOriginalNode()->attrGroups;
		$errors = $this->attributesCheck->check(
			$scope,
			$attrGroups,
			Attribute::TARGET_METHOD,
			'method',
		);

		foreach ($attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attribute) {
				$name = $attribute->name->toString();
				if (strtolower($name) === 'nodiscard') {
					$errors[] = RuleErrorBuilder::message(sprintf('Attribute class %s cannot be used on property hooks.', $name))
						->identifier('attribute.target')
						->line($attribute->getStartLine())
						->nonIgnorable()
						->build();
					break;
				}
			}
		}

		return $errors;
	}

}
