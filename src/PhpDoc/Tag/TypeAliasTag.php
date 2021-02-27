<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

class TypeAliasTag
{

	private string $alias;

	private string $type;

	public function __construct(string $alias, string $type)
	{
		$this->alias = $alias;
		$this->type = $type;
	}

	public function getAlias(): string
	{
		return $this->alias;
	}

	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @param mixed[] $properties
	 * @return TypeAliasTag
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['alias'],
			$properties['type']
		);
	}

}
