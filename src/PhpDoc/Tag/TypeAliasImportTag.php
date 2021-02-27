<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

final class TypeAliasImportTag
{

	private string $importedAlias;

	private \PHPStan\Type\Type $importedFrom;

	private ?string $importedAs;

	public function __construct(string $importedAlias, \PHPStan\Type\Type $importedFrom, ?string $importedAs)
	{
		$this->importedAlias = $importedAlias;
		$this->importedFrom = $importedFrom;
		$this->importedAs = $importedAs;
	}

	public function getImportedAlias(): string
	{
		return $this->importedAlias;
	}

	public function getImportedFrom(): \PHPStan\Type\Type
	{
		return $this->importedFrom;
	}

	public function getImportedAs(): ?string
	{
		return $this->importedAs;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['importedAlias'],
			$properties['importedFrom'],
			$properties['importedAs']
		);
	}

}
