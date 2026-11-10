<?php declare(strict_types = 1);

namespace ResultCacheE2ESymbolLookupsRules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * The same as ReplacementRule, for the calls collected by LegacyCallCollector.
 *
 * @implements Rule<CollectedDataNode>
 */
final class CollectedReplacementRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->reflectionProvider->hasClass('ResultCacheE2ESymbolLookups\Lib\CollectedReplacement')) {
			return [];
		}

		$errors = [];
		foreach ($node->get(LegacyCallCollector::class) as $file => $lines) {
			foreach ($lines as $line) {
				$errors[] = RuleErrorBuilder::message('Use CollectedReplacement instead of collectedLegacyCall().')
					->identifier('e2e.collectedReplacement')
					->file($file)
					->line($line)
					->build();
			}
		}

		return $errors;
	}

}
