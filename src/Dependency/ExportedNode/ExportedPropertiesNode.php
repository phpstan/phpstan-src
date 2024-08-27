<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;

final class ExportedPropertiesNode implements JsonSerializable, ExportedNode
{

	/**
	 * @param string[] $names
	 * @param ExportedAttributeNode[] $attributes
	 */
	public function __construct(
		private array $names,
		private ?ExportedPhpDocNode $phpDoc,
		private ?string $type,
		private bool $public,
		private bool $private,
		private bool $static,
		private bool $readonly,
		private array $attributes,
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

		if (count($this->names) !== count($node->names)) {
			return false;
		}

		foreach ($this->names as $i => $name) {
			if ($name !== $node->names[$i]) {
				return false;
			}
		}

		if (count($this->attributes) !== count($node->attributes)) {
			return false;
		}

		foreach ($this->attributes as $i => $attribute) {
			if (!$attribute->equals($node->attributes[$i])) {
				return false;
			}
		}

		return $this->type === $node->type
			&& $this->public === $node->public
			&& $this->private === $node->private
			&& $this->static === $node->static
			&& $this->readonly === $node->readonly;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): ExportedNode
	{
		return new self(
			$properties['names'],
			$properties['phpDoc'],
			$properties['type'],
			$properties['public'],
			$properties['private'],
			$properties['static'],
			$properties['readonly'],
			$properties['attributes'],
		);
	}

	/**
	 * @param mixed[] $data
	 * @return self
	 */
	public static function decode(array $data): ExportedNode
	{
		return new self(
			$data['names'],
			$data['phpDoc'] !== null ? ExportedPhpDocNode::decode($data['phpDoc']['data']) : null,
			$data['type'],
			$data['public'],
			$data['private'],
			$data['static'],
			$data['readonly'],
			array_map(static function (array $attributeData): ExportedAttributeNode {
				if ($attributeData['type'] !== ExportedAttributeNode::class) {
					throw new ShouldNotHappenException();
				}
				return ExportedAttributeNode::decode($attributeData['data']);
			}, $data['attributes']),
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
				'names' => $this->names,
				'phpDoc' => $this->phpDoc,
				'type' => $this->type,
				'public' => $this->public,
				'private' => $this->private,
				'static' => $this->static,
				'readonly' => $this->readonly,
				'attributes' => $this->attributes,
			],
		];
	}

}
