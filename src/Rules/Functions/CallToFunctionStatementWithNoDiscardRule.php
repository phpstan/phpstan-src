<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
#[RegisteredRule(level: 4)]
final class CallToFunctionStatementWithNoDiscardRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Expression::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->expr instanceof Node\Expr\FuncCall) {
			return [];
		}

		$funcCall = $node->expr;
		if (!($funcCall->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($funcCall->name, $scope)) {
			return [];
		}

		$function = $this->reflectionProvider->getFunction($funcCall->name, $scope);

		$attributes = $function->getAttributes();
		$hasNoDiscard = false;
		foreach ($attributes as $attrib) {
			if ($attrib->getName() === 'NoDiscard') {
				$hasNoDiscard = true;
				break;
			}
		}
		if (!$hasNoDiscard) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Call to function %s() on a separate line discards return value.',
				$function->getName(),
			))->identifier('function.resultDiscarded')->build(),
		];
	}

}
