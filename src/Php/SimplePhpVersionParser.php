<?php declare(strict_types = 1);

namespace PHPStan\Php;

use Nette\Utils\Strings;
use function sprintf;

final class SimplePhpVersionParser
{

	public static function parseVersion(string $version): ?PhpVersion
	{
		$matches = Strings::match($version, '#^(\d+)\.(\d+)(?:\.(\d+))?#');
		if ($matches === null) {
			return null;
		}

		$major = (int) $matches[1];
		$minor = (int) $matches[2];
		$patch = (int) ($matches[3] ?? 0);
		$versionId = (int) sprintf('%d%02d%02d', $major, $minor, $patch);

		return new PhpVersion($versionId);
	}

}
