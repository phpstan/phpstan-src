<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\File\RelativePathHelper;
use PHPStan\Node\InFunctionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * @implements Rule<InFunctionNode>
 */
#[ValidatesStubFiles]
final class DuplicateFunctionDeclarationRule implements Rule
{

	/** @var array<non-empty-string, list<ReflectionFunction>>|null */
	private ?array $functionMap = null;

	public function __construct(private Reflector $reflector, private RelativePathHelper $relativePathHelper)
	{
	}

	public function getNodeType(): string
	{
		return InFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$thisFunction = $node->getFunctionReflection();
		$functionName = $thisFunction->getName();

		if ($this->functionMap === null) {
			$this->functionMap = [];

			$allFunctions = $this->reflector->reflectAllFunctions();
			$filteredFunctions = [];
			foreach ($allFunctions as $reflectionFunction) {
				$reflectionFunctionName = $reflectionFunction->getName();
				if (!isset($this->functionMap[$reflectionFunctionName])) {
					$this->functionMap[$reflectionFunctionName] = [];
				}
				$this->functionMap[$reflectionFunctionName][] = $reflectionFunction;
			}
		}

		if (!isset($this->functionMap[$functionName]) || count($this->functionMap[$functionName]) < 2) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				"Function %s declared multiple times:\n%s",
				$functionName,
				implode("\n", array_map(fn (ReflectionFunction $function) => sprintf('- %s:%d', $this->relativePathHelper->getRelativePath($function->getFileName() ?? 'unknown'), $function->getStartLine()), $this->functionMap[$functionName])),
			))->identifier('function.duplicate')->build(),
		];
	}

}
