<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<CollectedDataNode>
 */
class CallToFunctionStatementWithoutImpurePointsRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functions = [];
		foreach ($node->get(FunctionWithoutImpurePointsCollector::class) as [$functionName]) {
			$functions[strtolower($functionName)] = $functionName;
		}

		$errors = [];
		foreach ($node->get(PossiblyPureFuncCallCollector::class) as $filePath => $data) {
			foreach ($data as [$func, $line]) {
				$lowerFunc = strtolower($func);
				if (!array_key_exists($lowerFunc, $functions)) {
					continue;
				}

				$originalFunctionName = $functions[$lowerFunc];
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Call to function %s() on a separate line has no effect.',
					$originalFunctionName,
				))->file($filePath)
					->line($line)
					->identifier('function.resultUnused')
					->build();
			}
		}

		return $errors;
	}

}
