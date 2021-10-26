<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;

class ExportedPropertyNode implements JsonSerializable, ExportedNode
{

	private string $name;

	private ?ExportedPhpDocNode $phpDoc;

	private ?string $type;

	private bool $public;

	private bool $private;

	private bool $static;

	private bool $readonly;

	public function __construct(
		string $name,
		?ExportedPhpDocNode $phpDoc,
		?string $type,
		bool $public,
		bool $private,
		bool $static,
		bool $readonly
	)
	{
		$this->name = $name;
		$this->phpDoc = $phpDoc;
		$this->type = $type;
		$this->public = $public;
		$this->private = $private;
		$this->static = $static;
		$this->readonly = $readonly;
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

		return $this->name === $node->name
			&& $this->type === $node->type
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
			$properties['name'],
			$properties['phpDoc'],
			$properties['type'],
			$properties['public'],
			$properties['private'],
			$properties['static'],
			$properties['readonly']
		);
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
			$data['type'],
			$data['public'],
			$data['private'],
			$data['static'],
			$data['readonly']
		);
	}

	/**
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return [
			'type' => self::class,
			'data' => [
				'name' => $this->name,
				'phpDoc' => $this->phpDoc,
				'type' => $this->type,
				'public' => $this->public,
				'private' => $this->private,
				'static' => $this->static,
				'readonly' => $this->readonly,
			],
		];
	}

}
