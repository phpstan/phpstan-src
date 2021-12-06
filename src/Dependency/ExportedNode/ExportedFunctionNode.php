<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;

class ExportedFunctionNode implements ExportedNode, JsonSerializable
{

	private string $name;

	private ?ExportedPhpDocNode $phpDoc;

	private bool $byRef;

	private ?string $returnType;

	/** @var ExportedParameterNode[] */
	private array $parameters;

	/**
	 * @param ExportedParameterNode[] $parameters
	 */
	public function __construct(
		string $name,
		?ExportedPhpDocNode $phpDoc,
		bool $byRef,
		?string $returnType,
		array $parameters
	)
	{
		$this->name = $name;
		$this->phpDoc = $phpDoc;
		$this->byRef = $byRef;
		$this->returnType = $returnType;
		$this->parameters = $parameters;
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
			$properties['parameters']
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
			}, $data['parameters'])
		);
	}

}
