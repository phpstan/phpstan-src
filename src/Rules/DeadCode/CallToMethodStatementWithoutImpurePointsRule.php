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
final class CallToMethodStatementWithoutImpurePointsRule implements Rule
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
				$className = strtolower($className);

				if (!array_key_exists($className, $methods)) {
					$methods[$className] = [];
				}
				$methods[$className][strtolower($methodName)] = $classDisplayName . '::' . $methodName;
			}
		}

		$errors = [];
		foreach ($node->get(PossiblyPureMethodCallCollector::class) as $filePath => $data) {
			foreach ($data as [$classNames, $method, $line]) {
				$originalMethodName = null;
				foreach ($classNames as $className) {
					$className = strtolower($className);

					if (!array_key_exists($className, $methods)) {
						continue 2;
					}

					$lowerMethod = strtolower($method);
					if (!array_key_exists($lowerMethod, $methods[$className])) {
						continue 2;
					}

					$originalMethodName = $methods[$className][$lowerMethod];
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'Call to method %s() on a separate line has no effect.',
					$originalMethodName,
				))->file($filePath)
					->line($line)
					->identifier('method.resultUnused')
					->build();
			}
		}

		return $errors;
	}

}
