<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use JsonSerializable;
use PhpParser\Node;
use ReturnTypeWillChange;

/**
 * @api
 * @final
 */
class CollectedData implements JsonSerializable
{

	/**
	 * @param mixed $data
	 * @param class-string<Collector<Node, mixed>> $collectorType
	 */
	public function __construct(
		private $data,
		private string $filePath,
		private string $collectorType,
	)
	{
	}

	public function getData(): mixed
	{
		return $this->data;
	}

	public function getFilePath(): string
	{
		return $this->filePath;
	}

	public function changeFilePath(string $newFilePath): self
	{
		return new self($this->data, $newFilePath, $this->collectorType);
	}

	/**
	 * @return class-string<Collector<Node, mixed>>
	 */
	public function getCollectorType(): string
	{
		return $this->collectorType;
	}

	/**
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return [
			'data' => $this->data,
			'filePath' => $this->filePath,
			'collectorType' => $this->collectorType,
		];
	}

	/**
	 * @param mixed[] $json
	 */
	public static function decode(array $json): self
	{
		return new self(
			$json['data'],
			$json['filePath'],
			$json['collectorType'],
		);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['data'],
			$properties['filePath'],
			$properties['collectorType'],
		);
	}

}
