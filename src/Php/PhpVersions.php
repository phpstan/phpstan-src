<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use function max;
use function min;

/**
 * @api
 */
final class PhpVersions
{

	private int $minVersionId;

	private int $maxVersionId;

	/**
	 * @param list<int> $phpVersionIds
	 */
	public function __construct(
		array $phpVersionIds,
	)
	{
		if ($phpVersionIds === []) {
			throw new ShouldNotHappenException();
		}

		$normalizedPhpVersionIds = [];
		foreach ($phpVersionIds as $versionId) {
			// drop patch version part and replace with 00
			$normalizedPhpVersionIds[] = ((int) ($versionId / 100)) * 100;
		}

		$this->minVersionId = min($normalizedPhpVersionIds);
		$this->maxVersionId = max($normalizedPhpVersionIds);
	}

	public function producesWarningForFinalPrivateMethods(): TrinaryLogic
	{
		return $this->minPhpVersion(80000);
	}

	private function minPhpVersion(int $versionId): TrinaryLogic
	{
		if ($this->minVersionId >= $versionId) {
			return TrinaryLogic::createYes();
		}
		if ($this->maxVersionId >= $versionId) {
			return TrinaryLogic::createMaybe();
		}
		return TrinaryLogic::createNo();
	}

}
