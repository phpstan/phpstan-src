<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function array_merge;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Interface_>
 */
class ApiInterfaceExtendsRule implements Rule
{

	public function __construct(
		private ApiRuleHelper $apiRuleHelper,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Interface_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->extends as $extends) {
			$errors = array_merge($errors, $this->checkName($scope, $extends));
		}

		return $errors;
	}

	/**
	 * @return RuleError[]
	 */
	private function checkName(Scope $scope, Node\Name $name): array
	{
		$extendedInterface = (string) $name;
		if (!$this->reflectionProvider->hasClass($extendedInterface)) {
			return [];
		}

		$extendedInterfaceReflection = $this->reflectionProvider->getClass($extendedInterface);
		if (!$this->apiRuleHelper->isPhpStanCode($scope, $extendedInterfaceReflection->getName(), $extendedInterfaceReflection->getFileName())) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Extending %s is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
			$extendedInterfaceReflection->getDisplayName(),
		))->identifier('phpstanApi.interface')->tip(sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		))->build();

		if (in_array($extendedInterfaceReflection->getName(), BcUncoveredInterface::CLASSES, true)) {
			return [$ruleError];
		}

		$docBlock = $extendedInterfaceReflection->getResolvedPhpDoc();
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
