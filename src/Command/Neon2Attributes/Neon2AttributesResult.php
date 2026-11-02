<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

final class Neon2AttributesResult
{

	/**
	 * @param array<string, string> $changedFiles new contents by path
	 * @param list<ConvertedService> $convertedServices
	 * @param list<KeptService> $keptServices
	 */
	public function __construct(
		private array $changedFiles,
		private array $convertedServices,
		private array $keptServices,
	)
	{
	}

	/**
	 * @return array<string, string>
	 */
	public function getChangedFiles(): array
	{
		return $this->changedFiles;
	}

	/**
	 * @return list<ConvertedService>
	 */
	public function getConvertedServices(): array
	{
		return $this->convertedServices;
	}

	/**
	 * @return list<KeptService>
	 */
	public function getKeptServices(): array
	{
		return $this->keptServices;
	}

}
