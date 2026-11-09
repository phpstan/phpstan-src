<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use function count;
use function sprintf;

#[AutowiredService]
final class ApiClassNameUsageExtension implements RestrictedClassNameUsageExtension
{

	public function __construct(private ApiRuleHelper $apiRuleHelper)
	{
	}

	public function isRestrictedClassNameUsage(
		ClassReflection $classReflection,
		Scope $scope,
		ClassNameUsageLocation $location,
	): ?RestrictedUsage
	{
		if ($location->value === ClassNameUsageLocation::CLASS_EXTENDS) {
			if ($classReflection->getName() === MutatingScope::class) {
				return null;
			}
			$message = 'Extending %s is not covered by backward compatibility promise. The class might change in a minor PHPStan version.';
			$identifier = 'phpstanApi.class';
			$isImplementing = false;
		} elseif ($location->value === ClassNameUsageLocation::CLASS_IMPLEMENTS) {
			$message = 'Implementing %s is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.';
			$identifier = 'phpstanApi.interface';
			$isImplementing = true;
		} elseif ($location->value === ClassNameUsageLocation::INTERFACE_EXTENDS) {
			$message = 'Extending %s is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.';
			$identifier = 'phpstanApi.interface';
			$isImplementing = true;
		} elseif ($location->value === ClassNameUsageLocation::TRAIT_USE) {
			$message = 'Using %s is not covered by backward compatibility promise. The trait might change in a minor PHPStan version.';
			$identifier = 'phpstanApi.trait';
			$isImplementing = false;
		} else {
			return null;
		}

		if (!$this->apiRuleHelper->isPhpStanCode($scope, $classReflection->getName(), $classReflection->getFileName())) {
			return null;
		}

		$restrictedUsage = RestrictedUsage::create(
			sprintf($message, $classReflection->getDisplayName()),
			$identifier,
			sprintf(
				"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
				'https://github.com/phpstan/phpstan/discussions',
			),
		);

		$docBlock = $classReflection->getResolvedPhpDoc();
		if ($docBlock === null) {
			return $restrictedUsage;
		}

		if ($isImplementing) {
			foreach ($docBlock->getPhpDocNodes() as $phpDocNode) {
				if (count($phpDocNode->getTagsByName('@api-do-not-implement')) > 0) {
					return $restrictedUsage;
				}
			}
		}

		foreach ($docBlock->getPhpDocNodes() as $phpDocNode) {
			if (count($phpDocNode->getTagsByName('@api')) > 0) {
				return null;
			}
		}

		return $restrictedUsage;
	}

}
