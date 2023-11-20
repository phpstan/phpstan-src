<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strpos;

/**
 * @implements Rule<Node\Expr\New_>
 */
class ApiInstantiationRule implements Rule
{

	public function __construct(
		private ApiRuleHelper $apiRuleHelper,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\New_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->class instanceof Node\Name) {
			return [];
		}

		$className = $scope->resolveName($node->class);
		if (!$this->reflectionProvider->hasClass($className)) {
			return [];
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if (!$this->apiRuleHelper->isPhpStanCode($scope, $classReflection->getName(), $classReflection->getFileName())) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Creating new %s is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
			$classReflection->getDisplayName(),
		))->identifier('phpstanApi.constructor')->tip(sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		))->build();

		if (!$classReflection->hasConstructor()) {
			return [$ruleError];
		}

		$constructor = $classReflection->getConstructor();
		$docComment = $constructor->getDocComment();
		if ($docComment === null) {
			return [$ruleError];
		}

		if (strpos($docComment, '@api') === false) {
			return [$ruleError];
		}

		if ($constructor->getDeclaringClass()->getName() !== $classReflection->getName()) {
			return [$ruleError];
		}

		return [];
	}

}
