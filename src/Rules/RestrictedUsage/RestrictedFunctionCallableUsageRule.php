<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FunctionCallableNode>
 */
#[AutowiredService]
final class RestrictedFunctionCallableUsageRule implements Rule
{

	/**
	 * @param ExtensionsCollection<RestrictedFunctionUsageExtension> $extensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: RestrictedFunctionUsageExtension::class)]
		private ExtensionsCollection $extensions,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return FunctionCallableNode::class;
	}

	/**
	 * @api
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->getName() instanceof Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->getName(), $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->getName(), $scope);

		$extensions = $this->extensions->getAll();
		$errors = [];

		foreach ($extensions as $extension) {
			$restrictedUsage = $extension->isRestrictedFunctionUsage($functionReflection, $scope);
			if ($restrictedUsage === null) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message($restrictedUsage->errorMessage)
				->identifier($restrictedUsage->identifier)
				->build();
		}

		return $errors;
	}

}
