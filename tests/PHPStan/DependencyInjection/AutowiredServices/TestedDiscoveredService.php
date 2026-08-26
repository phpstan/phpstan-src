<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AutowiredServices;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class TestedDiscoveredService
{

	public function __construct(
		#[AutowiredParameter]
		private string $tmpDir,
		#[AutowiredParameter(ref: '%featureToggles.bleedingEdge%')]
		private bool $bleedingEdge,
	)
	{
	}

	public function getTmpDir(): string
	{
		return $this->tmpDir;
	}

	public function isBleedingEdge(): bool
	{
		return $this->bleedingEdge;
	}

}
