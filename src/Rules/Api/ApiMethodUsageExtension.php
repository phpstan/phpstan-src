<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use function count;
use function sprintf;
use function str_contains;
use function strtolower;

#[AutowiredService]
final class ApiMethodUsageExtension implements RestrictedMethodUsageExtension
{

	public function __construct(private ApiRuleHelper $apiRuleHelper)
	{
	}

	public function isRestrictedMethodUsage(
		ExtendedMethodReflection $methodReflection,
		Scope $scope,
	): ?RestrictedUsage
	{
		// new Foo() is checked by ApiInstantiationRule, and a class that may be extended may call parent::__construct()
		if (strtolower($methodReflection->getName()) === '__construct') {
			return null;
		}

		// resolves the real declaring class when called again with the declaring class rewritten to the called subclass
		$declaringClass = $methodReflection->getDeclaringClass();
		if ($declaringClass->hasNativeMethod($methodReflection->getName())) {
			$methodReflection = $declaringClass->getNativeMethod($methodReflection->getName());
			$declaringClass = $methodReflection->getDeclaringClass();
		}

		if (!$this->apiRuleHelper->isPhpStanCode($scope, $declaringClass->getName(), $declaringClass->getFileName())) {
			return null;
		}

		if ($this->isCovered($methodReflection)) {
			return null;
		}

		return RestrictedUsage::create(
			sprintf(
				'Calling %s::%s() is not covered by backward compatibility promise. The method might change in a minor PHPStan version.',
				$declaringClass->getDisplayName(),
				$methodReflection->getName(),
			),
			'phpstanApi.method',
			sprintf(
				"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
				'https://github.com/phpstan/phpstan/discussions',
			),
		);
	}

	private function isCovered(ExtendedMethodReflection $methodReflection): bool
	{
		$classDocBlock = $methodReflection->getDeclaringClass()->getResolvedPhpDoc();
		if ($classDocBlock !== null) {
			foreach ($classDocBlock->getPhpDocNodes() as $phpDocNode) {
				if (count($phpDocNode->getTagsByName('@api')) > 0) {
					return true;
				}
			}
		}

		$methodDocComment = $methodReflection->getDocComment();
		if ($methodDocComment === null) {
			return false;
		}

		return str_contains($methodDocComment, '@api');
	}

}
