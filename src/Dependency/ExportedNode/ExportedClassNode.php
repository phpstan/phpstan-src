<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;

class ExportedClassNode implements ExportedNode, JsonSerializable
{

	/**
	 * @param string[] $implements
	 * @param string[] $usedTraits
	 * @param ExportedTraitUseAdaptation[] $traitUseAdaptations
	 * @param ExportedNode[] $statements
	 */
	public function __construct(
		private string $name,
		private ?ExportedPhpDocNode $phpDoc,
		private bool $abstract,
		private bool $final,
		private ?string $extends,
		private array $implements,
		private array $usedTraits,
		private array $traitUseAdaptations,
		private array $statements,
	)
	{
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		if ($this->phpDoc === null) {
			if ($node->phpDoc !== null) {
				return false;
			}
		} elseif ($node->phpDoc !== null) {
			if (!$this->phpDoc->equals($node->phpDoc)) {
				return false;
			}
		} else {
			return false;
		}

		if (count($this->traitUseAdaptations) !== count($node->traitUseAdaptations)) {
			return false;
		}

		foreach ($this->traitUseAdaptations as $i => $ourTraitUseAdaptation) {
			$theirTraitUseAdaptation = $node->traitUseAdaptations[$i];
			if (!$ourTraitUseAdaptation->equals($theirTraitUseAdaptation)) {
				return false;
			}
		}

		if (count($this->statements) !== count($node->statements)) {
			return false;
		}

		foreach ($this->statements as $i => $statement) {
			if ($statement->equals($node->statements[$i])) {
				continue;
			}

			return false;
		}

		return $this->name === $node->name
			&& $this->abstract === $node->abstract
			&& $this->final === $node->final
			&& $this->extends === $node->extends
			&& $this->implements === $node->implements
			&& $this->usedTraits === $node->usedTraits;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): ExportedNode
	{
		return new self(
			$properties['name'],
			$properties['phpDoc'],
			$properties['abstract'],
			$properties['final'],
			$properties['extends'],
			$properties['implements'],
			$properties['usedTraits'],
			$properties['traitUseAdaptations'],
			$properties['statements'],
		);
	}

	/**
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return [
			'type' => self::class,
			'data' => [
				'name' => $this->name,
				'phpDoc' => $this->phpDoc,
				'abstract' => $this->abstract,
				'final' => $this->final,
				'extends' => $this->extends,
				'implements' => $this->implements,
				'usedTraits' => $this->usedTraits,
				'traitUseAdaptations' => $this->traitUseAdaptations,
				'statements' => $this->statements,
			],
		];
	}

	/**
	 * @param mixed[] $data
	 * @return self
	 */
	public static function decode(array $data): ExportedNode
	{
		return new self(
			$data['name'],
			$data['phpDoc'] !== null ? ExportedPhpDocNode::decode($data['phpDoc']['data']) : null,
			$data['abstract'],
			$data['final'],
			$data['extends'],
			$data['implements'],
			$data['usedTraits'],
			array_map(static function (array $traitUseAdaptationData): ExportedTraitUseAdaptation {
				if ($traitUseAdaptationData['type'] !== ExportedTraitUseAdaptation::class) {
					throw new ShouldNotHappenException();
				}
				return ExportedTraitUseAdaptation::decode($traitUseAdaptationData['data']);
			}, $data['traitUseAdaptations']),
			array_map(static function (array $node): ExportedNode {
				$nodeType = $node['type'];

				return $nodeType::decode($node['data']);
			}, $data['statements']),
		);
	}

}
