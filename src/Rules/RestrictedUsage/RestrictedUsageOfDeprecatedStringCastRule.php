<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node;
use PhpParser\Node\Expr\Cast;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Cast\String_>
 */
#[AutowiredService]
final class RestrictedUsageOfDeprecatedStringCastRule implements Rule
{

	/**
	 * @param ExtensionsCollection<RestrictedMethodUsageExtension> $extensions
	 */
	public function __construct(
		#[AutowiredExtensions(interface: RestrictedMethodUsageExtension::class)]
		private ExtensionsCollection $extensions,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Cast\String_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$extensions = $this->extensions->getAll();
		if ($extensions === []) {
			return [];
		}

		$exprType = $scope->getType($node->expr);
		$referencedClasses = $exprType->getObjectClassNames();

		$errors = [];

		foreach ($referencedClasses as $referencedClass) {
			if (!$this->reflectionProvider->hasClass($referencedClass)) {
				continue;
			}

			$classReflection = $this->reflectionProvider->getClass($referencedClass);
			if (!$classReflection->hasNativeMethod('__toString')) {
				continue;
			}

			$methodReflection = $classReflection->getNativeMethod('__toString');
			foreach ($extensions as $extension) {
				$restrictedUsage = $extension->isRestrictedMethodUsage($methodReflection, $scope);
				if ($restrictedUsage === null) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message($restrictedUsage->errorMessage)
					->identifier($restrictedUsage->identifier)
					->build();
			}
		}

		return $errors;
	}

}
