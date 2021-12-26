<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use ReturnTypeWillChange;
use function array_map;
use function count;

class ExportedEnumNode implements ExportedNode, JsonSerializable
{

	private string $name;

	private ?string $scalarType;

	private ?ExportedPhpDocNode $phpDoc;

	/** @var string[] */
	private array $implements;

	/** @var ExportedNode[] */
	private array $statements;

	/**
	 * @param string[] $implements
	 * @param ExportedNode[] $statements
	 */
	public function __construct(string $name, ?string $scalarType, ?ExportedPhpDocNode $phpDoc, array $implements, array $statements)
	{
		$this->name = $name;
		$this->scalarType = $scalarType;
		$this->phpDoc = $phpDoc;
		$this->implements = $implements;
		$this->statements = $statements;
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
			&& $this->scalarType === $node->scalarType
			&& $this->implements === $node->implements;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): ExportedNode
	{
		return new self(
			$properties['name'],
			$properties['scalarType'],
			$properties['phpDoc'],
			$properties['implements'],
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
				'scalarType' => $this->scalarType,
				'phpDoc' => $this->phpDoc,
				'implements' => $this->implements,
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
			$data['scalarType'],
			$data['phpDoc'] !== null ? ExportedPhpDocNode::decode($data['phpDoc']['data']) : null,
			$data['implements'],
			array_map(static function (array $node): ExportedNode {
				$nodeType = $node['type'];

				return $nodeType::decode($node['data']);
			}, $data['statements']),
		);
	}

}
