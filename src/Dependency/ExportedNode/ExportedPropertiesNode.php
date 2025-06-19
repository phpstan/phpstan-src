<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use Override;
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
	 * @param ExportedPropertyHookNode[] $hooks
	 */
	public function __construct(
		private array $names,
		private ?ExportedPhpDocNode $phpDoc,
		private ?string $type,
		private bool $public,
		private bool $private,
		private bool $static,
		private bool $readonly,
		private bool $abstract,
		private bool $final,
		private bool $publicSet,
		private bool $protectedSet,
		private bool $privateSet,
		private bool $virtual,
		private array $attributes,
		private array $hooks,
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

		if (count($this->hooks) !== count($node->hooks)) {
			return false;
		}

		foreach ($this->hooks as $i => $hook) {
			if (!$hook->equals($node->hooks[$i])) {
				return false;
			}
		}

		return $this->type === $node->type
			&& $this->public === $node->public
			&& $this->private === $node->private
			&& $this->static === $node->static
			&& $this->readonly === $node->readonly
			&& $this->abstract === $node->abstract
			&& $this->final === $node->final
			&& $this->publicSet === $node->publicSet
			&& $this->protectedSet === $node->protectedSet
			&& $this->privateSet === $node->privateSet
			&& $this->virtual === $node->virtual;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['names'],
			$properties['phpDoc'],
			$properties['type'],
			$properties['public'],
			$properties['private'],
			$properties['static'],
			$properties['readonly'],
			$properties['abstract'],
			$properties['final'],
			$properties['publicSet'],
			$properties['protectedSet'],
			$properties['privateSet'],
			$properties['virtual'],
			$properties['attributes'],
			$properties['hooks'],
		);
	}

	/**
	 * @param mixed[] $data
	 */
	public static function decode(array $data): self
	{
		return new self(
			$data['names'],
			$data['phpDoc'] !== null ? ExportedPhpDocNode::decode($data['phpDoc']['data']) : null,
			$data['type'],
			$data['public'],
			$data['private'],
			$data['static'],
			$data['readonly'],
			$data['abstract'],
			$data['final'],
			$data['publicSet'],
			$data['protectedSet'],
			$data['privateSet'],
			$data['virtual'],
			array_map(static function (array $attributeData): ExportedAttributeNode {
				if ($attributeData['type'] !== ExportedAttributeNode::class) {
					throw new ShouldNotHappenException();
				}
				return ExportedAttributeNode::decode($attributeData['data']);
			}, $data['attributes']),
			array_map(static function (array $attributeData): ExportedPropertyHookNode {
				if ($attributeData['type'] !== ExportedPropertyHookNode::class) {
					throw new ShouldNotHappenException();
				}
				return ExportedPropertyHookNode::decode($attributeData['data']);
			}, $data['hooks']),
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
				'names' => $this->names,
				'phpDoc' => $this->phpDoc,
				'type' => $this->type,
				'public' => $this->public,
				'private' => $this->private,
				'static' => $this->static,
				'readonly' => $this->readonly,
				'abstract' => $this->abstract,
				'final' => $this->final,
				'publicSet' => $this->publicSet,
				'protectedSet' => $this->protectedSet,
				'privateSet' => $this->privateSet,
				'virtual' => $this->virtual,
				'attributes' => $this->attributes,
				'hooks' => $this->hooks,
			],
		];
	}

}
