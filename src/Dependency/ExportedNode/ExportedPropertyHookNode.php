<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use Override;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;

final class ExportedPropertyHookNode implements ExportedNode, JsonSerializable
{

	/**
	 * @param ExportedParameterNode[] $parameters
	 * @param ExportedAttributeNode[] $attributes
	 */
	public function __construct(
		private string $name,
		private ?ExportedPhpDocNode $phpDoc,
		private bool $byRef,
		private bool $abstract,
		private bool $final,
		private bool $short,
		private array $parameters,
		private array $attributes,
	)
	{
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		if (count($this->parameters) !== count($node->parameters)) {
			return false;
		}

		foreach ($this->parameters as $i => $ourParameter) {
			$theirParameter = $node->parameters[$i];
			if (!$ourParameter->equals($theirParameter)) {
				return false;
			}
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

		if (count($this->attributes) !== count($node->attributes)) {
			return false;
		}

		foreach ($this->attributes as $i => $attribute) {
			if (!$attribute->equals($node->attributes[$i])) {
				return false;
			}
		}

		return $this->name === $node->name
			&& $this->byRef === $node->byRef
			&& $this->abstract === $node->abstract
			&& $this->final === $node->final
			&& $this->short === $node->short;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['name'],
			$properties['phpDoc'],
			$properties['byRef'],
			$properties['abstract'],
			$properties['final'],
			$properties['short'],
			$properties['parameters'],
			$properties['attributes'],
		);
	}

	/**
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	#[Override]
	public function jsonSerialize()
	{
		return [
			'type' => self::class,
			'data' => [
				'name' => $this->name,
				'phpDoc' => $this->phpDoc,
				'byRef' => $this->byRef,
				'abstract' => $this->abstract,
				'final' => $this->final,
				'short' => $this->short,
				'parameters' => $this->parameters,
				'attributes' => $this->attributes,
			],
		];
	}

	/**
	 * @param mixed[] $data
	 */
	public static function decode(array $data): self
	{
		return new self(
			$data['name'],
			$data['phpDoc'] !== null ? ExportedPhpDocNode::decode($data['phpDoc']['data']) : null,
			$data['byRef'],
			$data['abstract'],
			$data['final'],
			$data['short'],
			array_map(static function (array $parameterData): ExportedParameterNode {
				if ($parameterData['type'] !== ExportedParameterNode::class) {
					throw new ShouldNotHappenException();
				}
				return ExportedParameterNode::decode($parameterData['data']);
			}, $data['parameters']),
			array_map(static function (array $attributeData): ExportedAttributeNode {
				if ($attributeData['type'] !== ExportedAttributeNode::class) {
					throw new ShouldNotHappenException();
				}
				return ExportedAttributeNode::decode($attributeData['data']);
			}, $data['attributes']),
		);
	}

}
