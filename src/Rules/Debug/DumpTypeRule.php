<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class DumpTypeRule implements Rule
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

		if (strtolower($functionName) !== 'phpstan\dumptype') {
			return [];
		}

		if ($node->getArgs() === []) {
			return [
				RuleErrorBuilder::message(sprintf('Missing argument for %s() function call.', $functionName))
					->nonIgnorable()
					->build(),
			];
		}

		return [
			RuleErrorBuilder::message(
				sprintf(
					'Dumped type: %s',
					$scope->getType($node->getArgs()[0]->value)->describe(VerbosityLevel::precise()),
				),
			)->nonIgnorable()->build(),
		];
	}

}
