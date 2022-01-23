<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

/** @api */
final class TypeAliasImportTag
{

	public function __construct(private string $importedAlias, private string $importedFrom, private ?string $importedAs)
	{
	}

	public function getImportedAlias(): string
	{
		return $this->importedAlias;
	}

	public function getImportedFrom(): string
	{
		return $this->importedFrom;
	}

	public function getImportedAs(): ?string
	{
		return $this->importedAs;
	}

}
