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

		$args = $node->getArgs();
		if (count($args) === 0) {
			return [];
		}

		$functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
		if ($functionName === null) {
			return [];
		}

		if (strtolower($functionName) !== 'phpstan\dumpphpdoctype') {
			return [];
		}

		$errors = [];
		foreach ($args as $arg) {
			$errors[] = RuleErrorBuilder::message(
				sprintf(
					'Dumped type: %s',
					$this->printer->print($scope->getType($arg->value)->toPhpDocNode()),
				),
			)->nonIgnorable()->identifier('phpstan.dumpPhpDocType')->build();
		}

		return $errors;
	}

}
