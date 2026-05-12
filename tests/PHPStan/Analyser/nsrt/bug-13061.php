<?php declare(strict_types = 1);

namespace Bug13061;

use function PHPStan\Testing\assertType;

interface ScenarioInterface {}

class ScenarioNode implements ScenarioInterface {}
class OutlineNode implements ScenarioInterface {}

class FeatureNode
{
	/**
	 * @param ScenarioInterface[] $scenarios
	 */
	public function __construct(
		public readonly ?string $title,
		public readonly array $scenarios,
	) {
	}
}

/**
 * @phpstan-type TFeatureHash array{title?: string|null, scenarios?: array<int, TScenarioHash|TOutlineHash>}
 * @phpstan-type TScenarioHash array{type?: 'scenario', title?: string|null}
 * @phpstan-type TOutlineHash array{type: 'outline', title?: string|null, examples?: array<array-key, TExampleTableHash>}
 * @phpstan-type TExampleTableHash array<int, list<string>>
 */
abstract class GherkinArrayLoader
{
	/**
	 * @phpstan-param TFeatureHash $hash
	 */
	protected function loadFeatureHash(array $hash, int $line = 0): FeatureNode
	{
		$hash = array_merge(
			[
				'title' => null,
				'scenarios' => [],
			],
			$hash
		);

		$scenarios = [];
		foreach ((array) $hash['scenarios'] as $scenarioIterator => $scenarioHash) {
			if (isset($scenarioHash['type']) && $scenarioHash['type'] === 'outline') {
				assertType("array{type: 'outline', title?: string|null, examples?: array<array<int, list<string>>>}", $scenarioHash);
				$scenarios[] = $this->loadOutlineHash($scenarioHash, $scenarioIterator);
			} else {
				assertType("array{type?: 'scenario', title?: string|null}", $scenarioHash);
				$scenarios[] = $this->loadScenarioHash($scenarioHash, $scenarioIterator);
			}
		}

		return new FeatureNode($hash['title'], $scenarios);
	}

	/**
	 * @phpstan-param TScenarioHash $hash
	 */
	abstract protected function loadScenarioHash(array $hash, int $line = 0): ScenarioNode;

	/**
	 * @phpstan-param TOutlineHash $hash
	 */
	abstract protected function loadOutlineHash(array $hash, int $line = 0): OutlineNode;
}
