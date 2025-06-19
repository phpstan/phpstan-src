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
 * @implements Rule<Node\Expr\ClassConstFetch>
 */
#[AutowiredService]
final class RestrictedClassConstantUsageRule implements Rule
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
		return Node\Expr\ClassConstFetch::class;
	}

	/**
	 * @api
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Identifier) {
			return [];
		}

		/** @var RestrictedClassConstantUsageExtension[] $extensions */
		$extensions = $this->container->getServicesByTag(RestrictedClassConstantUsageExtension::CLASS_CONSTANT_EXTENSION_TAG);
		if ($extensions === []) {
			return [];
		}

		$constantName = $node->name->name;
		$referencedClasses = [];

		if ($node->class instanceof Name) {
			$referencedClasses[] = $scope->resolveName($node->class);
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->class,
				'', // We don't care about the error message
				static fn (Type $type): bool => $type->canAccessConstants()->yes() && $type->hasConstant($constantName)->yes(),
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
			if (!$classReflection->hasConstant($constantName)) {
				continue;
			}

			$constantReflection = $classReflection->getConstant($constantName);
			foreach ($extensions as $extension) {
				$restrictedUsage = $extension->isRestrictedClassConstantUsage($constantReflection, $scope);
				if ($restrictedUsage === null) {
					continue;
				}

				if ($classReflection->getName() !== $constantReflection->getDeclaringClass()->getName()) {
					$rewrittenConstantReflection = new RewrittenDeclaringClassClassConstantReflection($classReflection, $constantReflection);
					$rewrittenRestrictedUsage = $extension->isRestrictedClassConstantUsage($rewrittenConstantReflection, $scope);
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
