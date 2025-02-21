<?php

declare(strict_types=1);

namespace App;

use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<CollectedDataNode>
 */
final class ClassRule implements Rule
{
	#[Override]
	public function getNodeType() : string
	{
		return CollectedDataNode::class;
	}

	#[Override]
	public function processNode(Node $node, Scope $scope) : array
	{
		$errors = [];

		foreach ($node->get(ClassCollector::class) as $file => $data) {
			foreach ($data as [$className, $line]) {
				$errors[] = RuleErrorBuilder::message('This is an error from a rule that uses a collector')
					->file($file)
					->line($line)
					->identifier('class.name')
					->build();
			}
		}

		return $errors;
	}

}
