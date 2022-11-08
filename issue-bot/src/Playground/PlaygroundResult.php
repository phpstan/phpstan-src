<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

class PlaygroundResult
{

	/**
	 * @param array<int, list<PlaygroundError>> $versionedErrors
	 */
	public function __construct(
		private string $url,
		private string $hash,
		private string $code,
		private string $level,
		private bool $strictRules,
		private bool $bleedingEdge,
		private bool $treatPhpDocTypesAsCertain,
		private array $versionedErrors,
	)
	{
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getHash(): string
	{
		return $this->hash;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getLevel(): string
	{
		return $this->level;
	}

	public function isStrictRules(): bool
	{
		return $this->strictRules;
	}

	public function isBleedingEdge(): bool
	{
		return $this->bleedingEdge;
	}

	public function isTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	/**
	 * @return array<int, list<PlaygroundError>>
	 */
	public function getVersionedErrors(): array
	{
		return $this->versionedErrors;
	}

}
