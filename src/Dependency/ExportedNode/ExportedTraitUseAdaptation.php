<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use ReturnTypeWillChange;

final class ExportedTraitUseAdaptation implements ExportedNode, JsonSerializable
{

	/**
	 * @param string[]|null $insteadOfs
	 */
	private function __construct(
		private ?string $traitName,
		private string $method,
		private ?int $newModifier,
		private ?string $newName,
		private ?array $insteadOfs,
	)
	{
	}

	public static function createAlias(
		?string $traitName,
		string $method,
		?int $newModifier,
		?string $newName,
	): self
	{
		return new self($traitName, $method, $newModifier, $newName, null);
	}

	/**
	 * @param string[] $insteadOfs
	 */
	public static function createPrecedence(
		?string $traitName,
		string $method,
		array $insteadOfs,
	): self
	{
		return new self($traitName, $method, null, null, $insteadOfs);
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		return $this->traitName === $node->traitName
			&& $this->method === $node->method
			&& $this->newModifier === $node->newModifier
			&& $this->newName === $node->newName
			&& $this->insteadOfs === $node->insteadOfs;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): ExportedNode
	{
		return new self(
			$properties['traitName'],
			$properties['method'],
			$properties['newModifier'],
			$properties['newName'],
			$properties['insteadOfs'],
		);
	}

	/**
	 * @param mixed[] $data
	 * @return self
	 */
	public static function decode(array $data): ExportedNode
	{
		return new self(
			$data['traitName'],
			$data['method'],
			$data['newModifier'],
			$data['newName'],
			$data['insteadOfs'],
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
				'traitName' => $this->traitName,
				'method' => $this->method,
				'newModifier' => $this->newModifier,
				'newName' => $this->newName,
				'insteadOfs' => $this->insteadOfs,
			],
		];
	}

}
