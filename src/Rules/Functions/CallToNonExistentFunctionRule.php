<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class CallToNonExistentFunctionRule implements Rule
{

	private ReflectionProvider $reflectionProvider;

	private bool $checkFunctionNameCase;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		bool $checkFunctionNameCase
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->checkFunctionNameCase = $checkFunctionNameCase;
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			if ($scope->isInFunctionExists($node->name->toString())) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf('Function %s not found.', (string) $node->name))->discoveringSymbolsTip()->build(),
			];
		}

		$function = $this->reflectionProvider->getFunction($node->name, $scope);
		$name = (string) $node->name;

		if ($this->checkFunctionNameCase) {
			/** @var string $calledFunctionName */
			$calledFunctionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
			if (
				strtolower($function->getName()) === strtolower($calledFunctionName)
				&& $function->getName() !== $calledFunctionName
			) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Call to function %s() with incorrect case: %s',
						$function->getName(),
						$name
					))->build(),
				];
			}
		}

		return [];
	}

}
