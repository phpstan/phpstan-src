<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Analyser\NameScope;

class TypeAliasTag
{

	private string $alias;

	private string $typeString;

	private NameScope $nameScope;

	public function __construct(
		string $alias,
		string $typeString,
		NameScope $nameScope
	)
	{
		$this->alias = $alias;
		$this->typeString = $typeString;
		$this->nameScope = $nameScope;
	}

	public function getAlias(): string
	{
		return $this->alias;
	}

	public function getTypeAlias(): \PHPStan\Type\TypeAlias
	{
		return new \PHPStan\Type\TypeAlias(
			$this->typeString,
			$this->nameScope
		);
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
