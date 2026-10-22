<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function implode;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[AutowiredService]
final class DebugScopeRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		$functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
		if ($functionName === null) {
			return [];
		}

		if (strtolower($functionName) !== 'phpstan\debugscope') {
			return [];
		}

		if (!$scope instanceof MutatingScope) {
			return [];
		}

		$parts = [];
		foreach ($scope->debug() as $key => $row) {
			$parts[] = sprintf('%s: %s', $key, $row);
		}

		if (count($parts) === 0) {
			$parts[] = 'Scope is empty';
		}

		return [
			RuleErrorBuilder::message(
				implode("\n", $parts),
			)->nonIgnorable()->identifier('phpstan.debugScope')->build(),
		];
	}

}
