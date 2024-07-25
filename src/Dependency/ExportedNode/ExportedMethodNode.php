<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;

final class ExportedMethodNode implements ExportedNode, JsonSerializable
{

	/**
	 * @param ExportedParameterNode[] $parameters
	 * @param ExportedAttributeNode[] $attributes
	 */
	public function __construct(
		private string $name,
		private ?ExportedPhpDocNode $phpDoc,
		private bool $byRef,
		private bool $public,
		private bool $private,
		private bool $abstract,
		private bool $final,
		private bool $static,
		private ?string $returnType,
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
			&& $this->public === $node->public
			&& $this->private === $node->private
			&& $this->abstract === $node->abstract
			&& $this->final === $node->final
			&& $this->static === $node->static
			&& $this->returnType === $node->returnType;
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
			$properties['byRef'],
			$properties['public'],
			$properties['private'],
			$properties['abstract'],
			$properties['final'],
			$properties['static'],
			$properties['returnType'],
			$properties['parameters'],
			$properties['attributes'],
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
				'byRef' => $this->byRef,
				'public' => $this->public,
				'private' => $this->private,
				'abstract' => $this->abstract,
				'final' => $this->final,
				'static' => $this->static,
				'returnType' => $this->returnType,
				'parameters' => $this->parameters,
				'attributes' => $this->attributes,
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
			$data['byRef'],
			$data['public'],
			$data['private'],
			$data['abstract'],
			$data['final'],
			$data['static'],
			$data['returnType'],
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
