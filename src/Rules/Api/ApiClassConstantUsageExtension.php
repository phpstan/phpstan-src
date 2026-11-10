<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedClassConstantUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use function count;
use function sprintf;
use function str_contains;

#[AutowiredService]
final class ApiClassConstantUsageExtension implements RestrictedClassConstantUsageExtension
{

	public function __construct(private ApiRuleHelper $apiRuleHelper)
	{
	}

	public function isRestrictedClassConstantUsage(
		ClassConstantReflection $constantReflection,
		Scope $scope,
	): ?RestrictedUsage
	{
		$declaringClass = $constantReflection->getDeclaringClass();
		if (!$this->apiRuleHelper->isPhpStanCode($scope, $declaringClass->getName(), $declaringClass->getFileName())) {
			return null;
		}

		if ($this->isClassCovered($declaringClass)) {
			return null;
		}

		if ($constantReflection->getName() === 'class') {
			// Foo::class is fine when the class has a method covered by the backward compatibility promise
			foreach ($declaringClass->getNativeReflection()->getMethods() as $methodReflection) {
				$methodDocComment = $methodReflection->getDocComment();
				if ($methodDocComment !== false && str_contains($methodDocComment, '@api')) {
					return null;
				}
			}
		} else {
			$constantDocComment = $constantReflection->getDocComment();
			if ($constantDocComment !== null && str_contains($constantDocComment, '@api')) {
				return null;
			}
		}

		return RestrictedUsage::create(
			sprintf(
				'Accessing %s::%s is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
				$declaringClass->getDisplayName(),
				$constantReflection->getName(),
			),
			'phpstanApi.classConstant',
			sprintf(
				"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
				'https://github.com/phpstan/phpstan/discussions',
			),
		);
	}

	private function isClassCovered(ClassReflection $classReflection): bool
	{
		$docBlock = $classReflection->getResolvedPhpDoc();
		if ($docBlock === null) {
			return false;
		}

		foreach ($docBlock->getPhpDocNodes() as $phpDocNode) {
			if (count($phpDocNode->getTagsByName('@api')) > 0) {
				return true;
			}
		}

		return false;
	}

}
