<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use Override;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;

final class ExportedParameterNode implements ExportedNode, JsonSerializable
{

	/**
	 * @param ExportedAttributeNode[] $attributes
	 */
	public function __construct(
		private string $name,
		private ?string $type,
		private bool $byRef,
		private bool $variadic,
		private bool $hasDefault,
		private array $attributes,
		private ?ExportedPhpDocNode $phpDoc = null,
		private int $flags = 0,
	)
	{
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
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

		return $this->name === $node->name
			&& $this->type === $node->type
			&& $this->byRef === $node->byRef
			&& $this->variadic === $node->variadic
			&& $this->hasDefault === $node->hasDefault
			&& $this->flags === $node->flags;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['name'],
			$properties['type'],
			$properties['byRef'],
			$properties['variadic'],
			$properties['hasDefault'],
			$properties['attributes'],
			$properties['phpDoc'] ?? null,
			$properties['flags'] ?? 0,
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
				'type' => $this->type,
				'byRef' => $this->byRef,
				'variadic' => $this->variadic,
				'hasDefault' => $this->hasDefault,
				'attributes' => $this->attributes,
				'phpDoc' => $this->phpDoc,
				'flags' => $this->flags,
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
			$data['type'],
			$data['byRef'],
			$data['variadic'],
			$data['hasDefault'],
			array_map(static function (array $attributeData): ExportedAttributeNode {
				if ($attributeData['type'] !== ExportedAttributeNode::class) {
					throw new ShouldNotHappenException();
				}
				return ExportedAttributeNode::decode($attributeData['data']);
			}, $data['attributes']),
			isset($data['phpDoc']) ? ExportedPhpDocNode::decode($data['phpDoc']['data']) : null,
			$data['flags'] ?? 0,
		);
	}

}
