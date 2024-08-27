<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\Dependency\RootExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;

final class ExportedFunctionNode implements RootExportedNode, JsonSerializable
{

	/**
	 * @param ExportedParameterNode[] $parameters
	 * @param ExportedAttributeNode[] $attributes
	 */
	public function __construct(
		private string $name,
		private ?ExportedPhpDocNode $phpDoc,
		private bool $byRef,
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

	/**
	 * @return self::TYPE_FUNCTION
	 */
	public function getType(): string
	{
		return self::TYPE_FUNCTION;
	}

	public function getName(): string
	{
		return $this->name;
	}

}
