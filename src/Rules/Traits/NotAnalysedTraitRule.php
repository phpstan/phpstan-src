<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<CollectedDataNode>
 */
#[RegisteredRule(level: 4)]
final class NotAnalysedTraitRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$traitDeclarationData = $node->get(TraitDeclarationCollector::class);
		// asked only when there is something to report: when a rule asks, no ignore is reported as unmatched
		// in an analysis of only files, as the rule may have skipped the errors it ignores
		if ($traitDeclarationData === [] || $node->isOnlyFilesAnalysis()) {
			return [];
		}

		$traitUseData = $node->get(TraitUseCollector::class);

		$declaredTraits = [];
		foreach ($traitDeclarationData as $file => $declaration) {
			foreach ($declaration as [$name, $line]) {
				$declaredTraits[strtolower($name)] = [$file, $name, $line];
			}
		}

		foreach ($traitUseData as $usedNamesData) {
			foreach ($usedNamesData as $usedNames) {
				foreach ($usedNames as $usedName) {
					unset($declaredTraits[strtolower($usedName)]);
				}
			}
		}

		$errors = [];
		foreach ($declaredTraits as [$file, $name, $line]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Trait %s is used zero times and is not analysed.',
				$name,
			))
				->file($file)
				->line($line)
				->identifier('trait.unused')
				->tip('See: https://phpstan.org/blog/how-phpstan-analyses-traits')
				->build();
		}

		return $errors;
	}

}
