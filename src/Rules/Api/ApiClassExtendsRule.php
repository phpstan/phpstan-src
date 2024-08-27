<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<Class_>
 */
final class ApiClassExtendsRule implements Rule
{

	public function __construct(
		private ApiRuleHelper $apiRuleHelper,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->extends === null) {
			return [];
		}

		$extendedClassName = (string) $node->extends;
		if (!$this->reflectionProvider->hasClass($extendedClassName)) {
			return [];
		}

		$extendedClassReflection = $this->reflectionProvider->getClass($extendedClassName);
		if (!$this->apiRuleHelper->isPhpStanCode($scope, $extendedClassReflection->getName(), $extendedClassReflection->getFileName())) {
			return [];
		}

		if ($extendedClassReflection->getName() === MutatingScope::class) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Extending %s is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
			$extendedClassReflection->getDisplayName(),
		))->identifier('phpstanApi.class')->tip(sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		))->build();

		$docBlock = $extendedClassReflection->getResolvedPhpDoc();
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
