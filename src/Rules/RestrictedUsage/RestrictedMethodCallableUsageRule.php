<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCallableNode>
 */
#[AutowiredService]
final class RestrictedMethodCallableUsageRule implements Rule
{

	public function __construct(
		private Container $container,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return MethodCallableNode::class;
	}

	/**
	 * @api
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getName() instanceof Identifier) {
			return [];
		}

		/** @var RestrictedMethodUsageExtension[] $extensions */
		$extensions = $this->container->getServicesByTag(RestrictedMethodUsageExtension::METHOD_EXTENSION_TAG);
		if ($extensions === []) {
			return [];
		}

		$methodName = $node->getName()->name;
		$methodCalledOnType = $scope->getType($node->getVar());
		$referencedClasses = $methodCalledOnType->getObjectClassNames();

		$errors = [];

		foreach ($referencedClasses as $referencedClass) {
			if (!$this->reflectionProvider->hasClass($referencedClass)) {
				continue;
			}

			$classReflection = $this->reflectionProvider->getClass($referencedClass);
			if (!$classReflection->hasMethod($methodName)) {
				continue;
			}

			$methodReflection = $classReflection->getMethod($methodName, $scope);
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
