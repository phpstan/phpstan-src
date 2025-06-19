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
 * @implements Rule<Node\Expr\StaticCall>
 */
#[AutowiredService]
final class RestrictedStaticMethodUsageRule implements Rule
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
		return Node\Expr\StaticCall::class;
	}

	/**
	 * @api
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Identifier) {
			return [];
		}

		/** @var RestrictedMethodUsageExtension[] $extensions */
		$extensions = $this->container->getServicesByTag(RestrictedMethodUsageExtension::METHOD_EXTENSION_TAG);
		if ($extensions === []) {
			return [];
		}

		$methodName = $node->name->name;
		$referencedClasses = [];

		if ($node->class instanceof Name) {
			$referencedClasses[] = $scope->resolveName($node->class);
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->class,
				'', // We don't care about the error message
				static fn (Type $type): bool => $type->canCallMethods()->yes() && $type->hasMethod($methodName)->yes(),
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
			if (!$classReflection->hasMethod($methodName)) {
				continue;
			}

			$methodReflection = $classReflection->getMethod($methodName, $scope);
			foreach ($extensions as $extension) {
				$restrictedUsage = $extension->isRestrictedMethodUsage($methodReflection, $scope);
				if ($restrictedUsage === null) {
					continue;
				}

				if ($classReflection->getName() !== $methodReflection->getDeclaringClass()->getName()) {
					$rewrittenMethodReflection = new RewrittenDeclaringClassMethodReflection($classReflection, $methodReflection);
					$rewrittenRestrictedUsage = $extension->isRestrictedMethodUsage($rewrittenMethodReflection, $scope);
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
