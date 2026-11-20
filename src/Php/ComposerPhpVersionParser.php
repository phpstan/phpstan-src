<?php declare(strict_types = 1);

namespace PHPStan\Php;

use Composer\Semver\VersionParser;
use Nette\Utils\Strings;
use function sprintf;

final class ComposerPhpVersionParser
{

	/**
	 * @param callable(string, int, bool):PhpVersion $buildPhpVersion
	 *
	 * @return array{PhpVersion|null, PhpVersion|null}
	 */
	public function parse(string $version, callable $buildPhpVersion): array
	{
		$minVersion = null;

		$parser = new VersionParser();
		$constraint = $parser->parseConstraints($version);

		$lowerBound = $constraint->getLowerBound();
		if (!$lowerBound->isZero()) {
			$minVersion = $this->buildVersion($lowerBound->getVersion(), $lowerBound->isInclusive() ? 0 : 1, false, $buildPhpVersion);
		}
		$upperBound = $constraint->getUpperBound();
		if ($upperBound->isPositiveInfinity()) {
			return [ $minVersion, null ];
		}

		$maxVersion = $this->buildVersion($upperBound->getVersion(), $upperBound->isInclusive() ? 0 : -1, true, $buildPhpVersion);
		return [ $minVersion, $maxVersion ];
	}

	/**
	 * @param callable(string, int, bool):PhpVersion $buildPhpVersion
	 */
	private function buildVersion(string $version, int $exclusiveBoundAdjustment, bool $isMaxVersion, callable $buildPhpVersion): ?PhpVersion
	{
		$matches = Strings::match($version, '#^(\d+)\.(\d+)(?:\.(\d+))?#');
		if ($matches === null) {
			return null;
		}

		$major = (int) $matches[1];
		$minor = (int) $matches[2];
		$patch = (int) ($matches[3] ?? 0);
		$versionId = (int) sprintf('%d%02d%02d', $major, $minor, $patch) + $exclusiveBoundAdjustment;

		return $buildPhpVersion($version, $versionId, $isMaxVersion);
	}

}
