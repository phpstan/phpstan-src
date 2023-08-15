<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\Traits\TraitDeclarationCollector;
use PHPStan\Rules\Traits\TraitUseCollector;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<CollectedDataNode>
 */
class NotAnalysedTraitRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$traitDeclarationData = $node->get(TraitDeclarationCollector::class);
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
				->tip('See: https://phpstan.org/blog/how-phpstan-analyses-traits')
				->build();
		}

		return $errors;
	}

}
