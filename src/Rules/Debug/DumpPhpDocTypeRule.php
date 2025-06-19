<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[AutowiredService]
final class DumpPhpDocTypeRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider, private Printer $printer)
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

		if (strtolower($functionName) !== 'phpstan\dumpphpdoctype') {
			return [];
		}

		if (count($node->getArgs()) === 0) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf(
					'Dumped type: %s',
					$this->printer->print($scope->getType($node->getArgs()[0]->value)->toPhpDocNode()),
				),
			)->nonIgnorable()->identifier('phpstan.dumpPhpDocType')->build(),
		];
	}

}
