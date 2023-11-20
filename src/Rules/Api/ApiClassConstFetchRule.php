<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;
use function strpos;

/**
 * @implements Rule<Node\Expr\ClassConstFetch>
 */
class ApiClassConstFetchRule implements Rule
{

	public function __construct(
		private ApiRuleHelper $apiRuleHelper,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\ClassConstFetch::class;
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
		if (!$this->apiRuleHelper->isPhpStanCode($scope, $classReflection->getName(), $classReflection->getFileName())) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Accessing %s::%s is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
			$classReflection->getDisplayName(),
			$node->name->toString(),
		))->identifier('phpstanApi.classConstant')->tip(sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		))->build();

		$docBlock = $classReflection->getResolvedPhpDoc();
		if ($docBlock !== null) {
			foreach ($docBlock->getPhpDocNodes() as $phpDocNode) {
				$apiTags = $phpDocNode->getTagsByName('@api');
				if (count($apiTags) > 0) {
					return [];
				}
			}
		}

		if ($node->name->toLowerString() === 'class') {
			foreach ($classReflection->getNativeReflection()->getMethods() as $methodReflection) {
				$methodDocComment = $methodReflection->getDocComment();
				if ($methodDocComment === false) {
					continue;
				}

				if (strpos($methodDocComment, '@api') === false) {
					continue;
				}

				return [];
			}
		}

		return [$ruleError];
	}

}
