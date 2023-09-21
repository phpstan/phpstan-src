<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_merge;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Class_>
 */
class ApiClassImplementsRule implements Rule
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
		$errors = [];
		foreach ($node->implements as $implements) {
			$errors = array_merge($errors, $this->checkName($scope, $implements));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkName(Scope $scope, Node\Name $name): array
	{
		$implementedClassName = (string) $name;
		if (!$this->reflectionProvider->hasClass($implementedClassName)) {
			return [];
		}

		$implementedClassReflection = $this->reflectionProvider->getClass($implementedClassName);
		if (!$this->apiRuleHelper->isPhpStanCode($scope, $implementedClassReflection->getName(), $implementedClassReflection->getFileName())) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Implementing %s is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
			$implementedClassReflection->getDisplayName(),
		))->identifier('phpstanApi.interface')->tip(sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		))->build();

		if (in_array($implementedClassReflection->getName(), BcUncoveredInterface::CLASSES, true)) {
			return [$ruleError];
		}

		$docBlock = $implementedClassReflection->getResolvedPhpDoc();
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
