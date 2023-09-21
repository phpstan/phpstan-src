<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;
use function strpos;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
class ApiStaticCallRule implements Rule
{

	public function __construct(
		private ApiRuleHelper $apiRuleHelper,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		if (!$node->class instanceof Node\Name) {
			return [];
		}

		$className = $scope->resolveName($node->class);
		if (!$this->reflectionProvider->hasClass($className)) {
			return [];
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		$methodName = $node->name->toString();
		if (!$classReflection->hasNativeMethod($methodName)) {
			return [];
		}

		$methodReflection = $classReflection->getNativeMethod($methodName);
		$declaringClass = $methodReflection->getDeclaringClass();
		if (!$this->apiRuleHelper->isPhpStanCode($scope, $declaringClass->getName(), $declaringClass->getFileName())) {
			return [];
		}

		if ($this->isCovered($methodReflection)) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Calling %s::%s() is not covered by backward compatibility promise. The method might change in a minor PHPStan version.',
			$declaringClass->getDisplayName(),
			$methodReflection->getName(),
		))->identifier('phpstanApi.method')->tip(sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		))->build();

		return [$ruleError];
	}

	private function isCovered(MethodReflection $methodReflection): bool
	{
		$declaringClass = $methodReflection->getDeclaringClass();
		$classDocBlock = $declaringClass->getResolvedPhpDoc();
		if ($methodReflection->getName() !== '__construct' && $classDocBlock !== null) {
			foreach ($classDocBlock->getPhpDocNodes() as $phpDocNode) {
				$apiTags = $phpDocNode->getTagsByName('@api');
				if (count($apiTags) > 0) {
					return true;
				}
			}
		}

		$methodDocComment = $methodReflection->getDocComment();
		if ($methodDocComment === null) {
			return false;
		}

		return strpos($methodDocComment, '@api') !== false;
	}

}
