<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node;
use PhpParser\Node\Expr\Cast;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Cast\String_>
 */
#[AutowiredService]
final class RestrictedUsageOfDeprecatedStringCastRule implements Rule
{

	public function __construct(
		private Container $container,
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
		/** @var RestrictedMethodUsageExtension[] $extensions */
		$extensions = $this->container->getServicesByTag(RestrictedMethodUsageExtension::METHOD_EXTENSION_TAG);
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
