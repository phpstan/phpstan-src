<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
#[RegisteredRule(level: 4)]
final class TooWideMethodReturnTypehintRule implements Rule
{

	public function __construct(
		#[AutowiredParameter(ref: '%checkTooWideReturnTypesInProtectedAndPublicMethods%')]
		private bool $checkProtectedAndPublicMethods,
		private TooWideTypeCheck $check,
	)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->isInTrait()) {
			return [];
		}
		$method = $node->getMethodReflection();
		$isFirstDeclaration = $method->getPrototype()->getDeclaringClass() === $method->getDeclaringClass();
		if (!$method->isPrivate()) {
			if (!$method->getDeclaringClass()->isFinal() && !$method->isFinal()->yes()) {
				if (!$this->checkProtectedAndPublicMethods) {
					return [];
				}

				if ($isFirstDeclaration) {
					return [];
				}
			}
		}

		return $this->check->checkFunction(
			$node,
			$method->getReturnType(),
			$method->getPhpDocReturnType(),
			sprintf(
				'Method %s::%s()',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			),
			!$isFirstDeclaration && !$method->isPrivate(),
			$scope,
		);
	}

}
