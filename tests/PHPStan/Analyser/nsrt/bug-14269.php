<?php declare(strict_types = 1);

namespace Bug14269;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Package
{

	public function isMultiDay(): bool
	{
		return true;
	}

	/**
	 * @return string[]
	 */
	public function getWorkspaces(): array
	{
		return [];
	}

	/**
	 * @return int[]|null
	 */
	public function getService(): ?array
	{
		return [];
	}

}

class ReturnsSelf
{

	public function subtract(): self
	{
		return $this;
	}

}

function doFoo(Package $package, ReturnsSelf $s): void {
	if (!$package->isMultiDay()) {
		$packageDurationInMinutes = 60;
	}

	foreach ($package->getWorkspaces() as $workplace) {
		$availableIntervals = $s->subtract();
		if ($package->getService() !== null && !$package->isMultiDay()) {
			assertVariableCertainty(TrinaryLogic::createYes(), $packageDurationInMinutes);
			continue;
		}

		$availableIntervals = $package->isMultiDay()
			? 'aaa'
			: 'bbb';
	}
}
