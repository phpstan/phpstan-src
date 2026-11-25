<?php declare(strict_types = 1);

namespace PHPStan\Php;

use Composer\Semver\VersionParser;
use Nette\Utils\Strings;
use function sprintf;
use function str_contains;

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
			// PHP_VERSION_ID can't tell a prerelease from the release, so only skip the bound itself
			// when it is a stable release: ">8.5.0-RC1" still allows PHP 8.5.0 (80500).
			$lowerVersion = $lowerBound->getVersion();
			$adjustment = !$lowerBound->isInclusive() && !str_contains($lowerVersion, '-') ? 1 : 0;
			$minVersion = $this->buildVersion($lowerVersion, $adjustment, false, $buildPhpVersion);
		}
		$upperBound = $constraint->getUpperBound();
		if ($upperBound->isPositiveInfinity()) {
			return [ $minVersion, null ];
		}

		// "<8.5" is normalized to "<8.5.0.0-dev", below every 8.5.0 prerelease, so 80499 is the maximum.
		// "<8.5.0.1" still allows 8.5.0, and "<8.5.0-RC1" still allows 8.5.0-beta.
		$upperVersion = $upperBound->getVersion();
		$adjustment = !$upperBound->isInclusive() && Strings::match($upperVersion, '#^(?:\d+\.){3}0+-dev$#') !== null ? -1 : 0;
		$maxVersion = $this->buildVersion($upperVersion, $adjustment, true, $buildPhpVersion);
		return [ $minVersion, $maxVersion ];
	}

	/**
	 * @param callable(string, int, bool):PhpVersion $buildPhpVersion
	 * @param -1|0|1 $exclusiveBoundAdjustment
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
