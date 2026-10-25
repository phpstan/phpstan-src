<?php declare(strict_types = 1);

namespace PHPStan\Analyser\CollectedDataReuse;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Asks for SecondCollector only when FirstCollector collected something, the pattern from
 * https://github.com/phpstan/phpstan/issues/15447#issuecomment-6389042558
 *
 * @implements Rule<CollectedDataNode>
 */
final class EarlyReturnRule implements Rule
{

	public int $runs = 0;

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$this->runs++;

		$first = $node->get(FirstCollector::class);
		if ($first === []) {
			return [];
		}

		$errors = [];
		foreach ($node->get(SecondCollector::class) as $file => $values) {
			foreach ($values as $value) {
				$errors[] = RuleErrorBuilder::message('Second collected ' . $value . '.')
					->file($file)
					->line(1)
					->identifier('tests.second')
					->build();
			}
		}

		return $errors;
	}

}
