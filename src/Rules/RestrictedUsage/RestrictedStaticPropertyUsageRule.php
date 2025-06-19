<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

/**
 * @implements Rule<Node\Expr\StaticPropertyFetch>
 */
#[AutowiredService]
final class RestrictedStaticPropertyUsageRule implements Rule
{

	public function __construct(
		private Container $container,
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\StaticPropertyFetch::class;
	}

	/**
	 * @api
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Identifier) {
			return [];
		}

		/** @var RestrictedPropertyUsageExtension[] $extensions */
		$extensions = $this->container->getServicesByTag(RestrictedPropertyUsageExtension::PROPERTY_EXTENSION_TAG);
		if ($extensions === []) {
			return [];
		}

		$propertyName = $node->name->name;
		$referencedClasses = [];

		if ($node->class instanceof Name) {
			$referencedClasses[] = $scope->resolveName($node->class);
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->class,
				'', // We don't care about the error message
				static fn (Type $type): bool => $type->canAccessProperties()->yes() && $type->hasProperty($propertyName)->yes(),
			);

			if ($classTypeResult->getType() instanceof ErrorType) {
				return [];
			}

			$referencedClasses = $classTypeResult->getReferencedClasses();
		}

		$errors = [];
		foreach ($referencedClasses as $referencedClass) {
			if (!$this->reflectionProvider->hasClass($referencedClass)) {
				continue;
			}

			$classReflection = $this->reflectionProvider->getClass($referencedClass);
			if (!$classReflection->hasProperty($propertyName)) {
				continue;
			}

			$propertyReflection = $classReflection->getProperty($propertyName, $scope);
			foreach ($extensions as $extension) {
				$restrictedUsage = $extension->isRestrictedPropertyUsage($propertyReflection, $scope);
				if ($restrictedUsage === null) {
					continue;
				}

				if ($classReflection->getName() !== $propertyReflection->getDeclaringClass()->getName()) {
					$rewrittenPropertyReflection = new RewrittenDeclaringClassPropertyReflection($classReflection, $propertyReflection);
					$rewrittenRestrictedUsage = $extension->isRestrictedPropertyUsage($rewrittenPropertyReflection, $scope);
					if ($rewrittenRestrictedUsage === null) {
						continue;
					}
				}

				$errors[] = RuleErrorBuilder::message($restrictedUsage->errorMessage)
					->identifier($restrictedUsage->identifier)
					->build();
			}
		}

		return $errors;
	}

}
