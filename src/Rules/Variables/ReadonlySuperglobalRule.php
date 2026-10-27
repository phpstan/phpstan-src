<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @implements Rule<VariableAssignNode>
 */
#[RegisteredRule(level: 0)]
final class ReadonlySuperglobalRule implements Rule
{

	public function __construct(
		#[AutowiredParameter]
		private bool $readonlySuperglobals,
	)
	{
	}

	public function getNodeType(): string
	{
		return VariableAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->readonlySuperglobals) {
			return [];
		}

		$variable = $node->getVariable();
		if (!is_string($variable->name) || !in_array($variable->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('Superglobal $%s is written to.', $variable->name))
				->identifier('assign.readonlySuperglobal')
				->tip('Superglobals are read-only because the readonlySuperglobals parameter is enabled.')
				->build(),
		];
	}

}
