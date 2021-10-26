<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;

class ExportedClassNode implements ExportedNode, JsonSerializable
{

	private string $name;

	private ?ExportedPhpDocNode $phpDoc;

	private bool $abstract;

	private bool $final;

	private ?string $extends;

	/** @var string[] */
	private array $implements;

	/** @var string[] */
	private array $usedTraits;

	/** @var ExportedTraitUseAdaptation[] */
	private array $traitUseAdaptations;

	/**
	 * @param string $name
	 * @param ExportedPhpDocNode|null $phpDoc
	 * @param bool $abstract
	 * @param bool $final
	 * @param string|null $extends
	 * @param string[] $implements
	 * @param string[] $usedTraits
	 * @param ExportedTraitUseAdaptation[] $traitUseAdaptations
	 */
	public function __construct(
		string $name,
		?ExportedPhpDocNode $phpDoc,
		bool $abstract,
		bool $final,
		?string $extends,
		array $implements,
		array $usedTraits,
		array $traitUseAdaptations
	)
	{
		$this->name = $name;
		$this->phpDoc = $phpDoc;
		$this->abstract = $abstract;
		$this->final = $final;
		$this->extends = $extends;
		$this->implements = $implements;
		$this->usedTraits = $usedTraits;
		$this->traitUseAdaptations = $traitUseAdaptations;
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
			$properties['traitUseAdaptations']
		);
	}

	/**
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
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
					throw new \PHPStan\ShouldNotHappenException();
				}
				return ExportedTraitUseAdaptation::decode($traitUseAdaptationData['data']);
			}, $data['traitUseAdaptations'])
		);
	}

}
