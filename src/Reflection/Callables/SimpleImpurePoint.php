<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PHPStan\Analyser\ImpurePoint;

/**
 * @phpstan-import-type ImpurePointIdentifier from ImpurePoint
 */
class SimpleImpurePoint
{

	/**
	 * @param ImpurePointIdentifier $identifier
	 */
	public function __construct(
		private string $identifier,
		private string $description,
		private bool $certain,
	)
	{
	}

	/**
	 * @return ImpurePointIdentifier
	 */
	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function isCertain(): bool
	{
		return $this->certain;
	}

}
