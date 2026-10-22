<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<CollectedDataNode>
 */
#[RegisteredRule(level: 4)]
final class CallToConstructorStatementWithoutImpurePointsRule implements Rule
{

	public function __construct(private PossiblyPureCallTransitivePurityResolver $purityResolver)
	{
	}

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$pureKeys = $this->purityResolver->getPureCallableKeys($node);

		$classesWithConstructors = [];
		foreach ($node->get(ConstructorWithoutImpurePointsCollector::class) as $collected) {
			foreach ($collected as [$class]) {
				if (!isset($pureKeys[PossiblyPureCallTransitivePurityResolver::methodKey($class, '__construct')])) {
					continue;
				}

				$classesWithConstructors[strtolower($class)] = $class;
			}
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
