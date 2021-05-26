<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Interface_>
 */
class ApiInterfaceExtendsRule implements Rule
{

	private ApiRuleHelper $apiRuleHelper;

	private ReflectionProvider $reflectionProvider;

	public function __construct(
		ApiRuleHelper $apiRuleHelper,
		ReflectionProvider $reflectionProvider
	)
	{
		$this->apiRuleHelper = $apiRuleHelper;
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getNodeType(): string
	{
		return Interface_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->apiRuleHelper->isInPhpStanNamespace($scope->getNamespace())) {
			return [];
		}

		$errors = [];
		foreach ($node->extends as $extends) {
			$errors = array_merge($errors, $this->checkName($extends));
		}

		return $errors;
	}

	/**
	 * @param Node\Name $name
	 * @return RuleError[]
	 */
	private function checkName(Node\Name $name): array
	{
		$extendedInterface = (string) $name;
		if (!$this->reflectionProvider->hasClass($extendedInterface)) {
			return [];
		}

		$extendedInterfaceReflection = $this->reflectionProvider->getClass($extendedInterface);
		if (!$this->apiRuleHelper->isInPhpStanNamespace($extendedInterfaceReflection->getName())) {
			return [];
		}

		if (in_array($extendedInterfaceReflection->getName(), ApiClassImplementsRule::INTERFACES, true)) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Extending %s is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
			$extendedInterfaceReflection->getDisplayName()
		))->tip(sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions'
		))->build();

		return [$ruleError];
	}

}
