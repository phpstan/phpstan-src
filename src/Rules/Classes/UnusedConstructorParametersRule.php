<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedFunctionParametersCheck;
use PHPStan\ShouldNotHappenException;
use function array_filter;
use function array_map;
use function array_values;
use function count;
use function is_string;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassMethodNode>
 */
final class UnusedConstructorParametersRule implements Rule
{

	public function __construct(private UnusedFunctionParametersCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();
		$originalNode = $node->getOriginalNode();
		if (strtolower($method->getName()) !== '__construct' || $originalNode->stmts === null) {
			return [];
		}

		if (count($originalNode->params) === 0) {
			return [];
		}

		$message = sprintf(
			'Constructor of class %s has an unused parameter $%%s.',
			SprintfHelper::escapeFormatString($node->getClassReflection()->getDisplayName()),
		);
		if ($node->getClassReflection()->isAnonymous()) {
			$message = 'Constructor of an anonymous class has an unused parameter $%s.';
		}

		return $this->check->getUnusedParameters(
			$scope,
			array_map(static function (Param $parameter): string {
				if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
					throw new ShouldNotHappenException();
				}
				return $parameter->var->name;
			}, array_values(array_filter($originalNode->params, static fn (Param $parameter): bool => $parameter->flags === 0))),
			$originalNode->stmts,
			$message,
			'constructor.unusedParameter',
		);
	}

}
