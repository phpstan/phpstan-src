<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<Attribute>
 */
#[RegisteredRule(level: 0)]
final class ApiAttributeRule implements Rule
{

	public function __construct(
		private ApiRuleHelper $apiRuleHelper,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Attribute::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$attributeClassName = $scope->resolveName($node->name);
		if (!$this->reflectionProvider->hasClass($attributeClassName)) {
			return [];
		}

		$attributeClassReflection = $this->reflectionProvider->getClass($attributeClassName);
		if (!$this->apiRuleHelper->isPhpStanCode($scope, $attributeClassReflection->getName(), $attributeClassReflection->getFileName())) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Using attribute %s is not covered by backward compatibility promise. The attribute might change in a minor PHPStan version.',
			$attributeClassReflection->getDisplayName(),
		))->identifier('phpstanApi.attribute')->tip(sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		))->build();

		$docBlock = $attributeClassReflection->getResolvedPhpDoc();
		if ($docBlock === null) {
			return [$ruleError];
		}

		foreach ($docBlock->getPhpDocNodes() as $phpDocNode) {
			$apiTags = $phpDocNode->getTagsByName('@api');
			if (count($apiTags) > 0) {
				return [];
			}
		}

		return [$ruleError];
	}

}
