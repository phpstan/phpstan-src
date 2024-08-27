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
final class CallToStaticMethodStatementWithoutImpurePointsRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methods = [];
		foreach ($node->get(MethodWithoutImpurePointsCollector::class) as $collected) {
			foreach ($collected as [$className, $methodName, $classDisplayName]) {
				$lowerClassName = strtolower($className);

				if (!array_key_exists($lowerClassName, $methods)) {
					$methods[$lowerClassName] = [];
				}
				$methods[$lowerClassName][strtolower($methodName)] = $classDisplayName . '::' . $methodName;
			}
		}

		$errors = [];
		foreach ($node->get(PossiblyPureStaticCallCollector::class) as $filePath => $data) {
			foreach ($data as [$className, $method, $line]) {
				$lowerClassName = strtolower($className);

				if (!array_key_exists($lowerClassName, $methods)) {
					continue;
				}

				$lowerMethod = strtolower($method);
				if (!array_key_exists($lowerMethod, $methods[$lowerClassName])) {
					continue;
				}

				$originalMethodName = $methods[$lowerClassName][$lowerMethod];

				$errors[] = RuleErrorBuilder::message(sprintf(
					'Call to %s() on a separate line has no effect.',
					$originalMethodName,
				))->file($filePath)
					->line($line)
					->identifier('staticMethod.resultUnused')
					->build();
			}
		}

		return $errors;
	}

}
