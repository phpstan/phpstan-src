<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;

#[AutowiredService]
final class ClassNameCheck
{

	/**
	 * @param ExtensionsCollection<RestrictedClassNameUsageExtension> $extensions
	 */
	public function __construct(
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private ClassForbiddenNameCheck $classForbiddenNameCheck,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredExtensions(interface: RestrictedClassNameUsageExtension::class)]
		private ExtensionsCollection $extensions,
	)
	{
	}

	/**
	 * @param ClassNameNodePair[] $pairs
	 * @return list<IdentifierRuleError>
	 */
	public function checkClassNames(
		Scope $scope,
		array $pairs,
		?ClassNameUsageLocation $location,
		bool $checkClassCaseSensitivity = true,
	): array
	{
		$errors = [];

		if ($checkClassCaseSensitivity) {
			foreach ($this->classCaseSensitivityCheck->checkClassNames($pairs) as $error) {
				$errors[] = $error;
			}
		}
		foreach ($this->classForbiddenNameCheck->checkClassNames($pairs) as $error) {
			$errors[] = $error;
		}

		if ($location === null) {
			return $errors;
		}

		$extensions = $this->extensions->getAll();
		if ($extensions === []) {
			return $errors;
		}

		foreach ($pairs as $pair) {
			if (!$this->reflectionProvider->hasClass($pair->getClassName())) {
				continue;
			}

			$classReflection = $this->reflectionProvider->getClass($pair->getClassName());
			foreach ($extensions as $extension) {
				$restrictedUsage = $extension->isRestrictedClassNameUsage($classReflection, $scope, $location);
				if ($restrictedUsage === null) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message($restrictedUsage->errorMessage)
					->identifier($restrictedUsage->identifier)
					->line($pair->getNode()->getStartLine())
					->build();
			}
		}

		return $errors;
	}

}
