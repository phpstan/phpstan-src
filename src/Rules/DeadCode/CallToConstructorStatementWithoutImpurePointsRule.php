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
class CallToConstructorStatementWithoutImpurePointsRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classesWithConstructors = [];
		foreach ($node->get(ConstructorWithoutImpurePointsCollector::class) as [$class]) {
			$classesWithConstructors[strtolower($class)] = $class;
		}

		$errors = [];
		foreach ($node->get(PossiblyPureNewCollector::class) as $filePath => $data) {
			foreach ($data as [$class, $line]) {
				$lowerClass = strtolower($class);
				if (!array_key_exists($lowerClass, $classesWithConstructors)) {
					continue;
				}

				$originalClassName = $classesWithConstructors[$lowerClass];
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Call to new %s() on a separate line has no effect.',
					$originalClassName,
				))->file($filePath)
					->line($line)
					->identifier('new.resultUnused')
					->build();
			}
		}

		return $errors;
	}

}
