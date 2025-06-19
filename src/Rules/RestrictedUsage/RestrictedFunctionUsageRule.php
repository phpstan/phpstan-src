<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[AutowiredService]
final class RestrictedFunctionUsageRule implements Rule
{

	public function __construct(
		private Container $container,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	/**
	 * @api
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);

		/** @var RestrictedFunctionUsageExtension[] $extensions */
		$extensions = $this->container->getServicesByTag(RestrictedFunctionUsageExtension::FUNCTION_EXTENSION_TAG);
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
