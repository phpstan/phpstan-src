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

		if (!$constraint->getLowerBound()->isZero()) {
			$minVersion = $this->buildVersion($constraint->getLowerBound()->getVersion(), false, $constraint->getLowerBound()->isInclusive(), $buildPhpVersion);
		}
		if ($constraint->getUpperBound()->isPositiveInfinity()) {
			return [ $minVersion, null ];
		}

		$maxVersion = $this->buildVersion($constraint->getUpperBound()->getVersion(), true, $constraint->getUpperBound()->isInclusive(), $buildPhpVersion);
		return [ $minVersion, $maxVersion ];
	}

	/**
	 * @param callable(string, int, bool):PhpVersion $buildPhpVersion
	 */
	private function buildVersion(string $version, bool $isMaxVersion, bool $isInclusive, callable $buildPhpVersion): ?PhpVersion
	{
		$matches = Strings::match($version, '#^(\d+)\.(\d+)(?:\.(\d+))?(?:\.(\d+))?(-.+)?#');
		if ($matches === null) {
			return null;
		}

		$major = (int) $matches[1];
		$minor = (int) $matches[2];
		$patch = (int) ($matches[3] ?? 0);
		$versionId = (int) sprintf('%d%02d%02d', $major, $minor, $patch);

		if (!$isInclusive) {
			if ($isMaxVersion) {
				// <8.5.2 is normalized to <8.5.2.0-dev, which excludes every 8.5.2 release and prerelease.
				// <8.5.2.1 still allows 8.5.2, and <8.5.2-RC1 or <8.5.2-stable still allow
				// a prerelease of 8.5.2, which has the same PHP_VERSION_ID.
				if ((int) ($matches[4] ?? 0) === 0 && ($matches[5] ?? '') === '-dev') {
					$versionId--;
				}
			} elseif (($matches[5] ?? '') === '') {
				// >8.2.3 excludes 8.2.3 itself, but >8.2.3-beta still allows the stable 8.2.3
				$versionId++;
			}
		}

		return $buildPhpVersion($version, $versionId, $isMaxVersion);
	}

}
