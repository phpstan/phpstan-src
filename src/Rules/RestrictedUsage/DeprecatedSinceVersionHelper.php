<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use Nette\Utils\Strings;
use PHPStan\Php\PhpVersions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Type\IntegerRangeType;
use function sprintf;
use function strtolower;

final class DeprecatedSinceVersionHelper
{

	/**
	 * @api
	 * @param list<AttributeReflection> $attributes
	 */
	public static function isScopeVersionBeforeDeprecation(array $attributes, PhpVersions $phpVersions): bool
	{
		$sinceVersionId = self::getDeprecatedSincePhpVersionId($attributes);
		if ($sinceVersionId === null) {
			return false;
		}

		return !IntegerRangeType::fromInterval($sinceVersionId, null)->isSuperTypeOf($phpVersions->getType())->yes();
	}

	/**
	 * @param list<AttributeReflection> $attributes
	 */
	private static function getDeprecatedSincePhpVersionId(array $attributes): ?int
	{
		foreach ($attributes as $attribute) {
			if (strtolower($attribute->getName()) !== 'deprecated') {
				continue;
			}
			$argumentTypes = $attribute->getArgumentTypes();
			if (!isset($argumentTypes['since'])) {
				continue;
			}
			$sinceType = $argumentTypes['since'];
			foreach ($sinceType->getConstantStrings() as $constantString) {
				$matches = Strings::match($constantString->getValue(), '#^(\d+)\.(\d+)(?:\.(\d+))?$#');
				if ($matches !== null) {
					$major = (int) $matches[1];
					$minor = (int) $matches[2];
					$patch = (int) ($matches[3] ?? 0);
					return (int) sprintf('%d%02d%02d', $major, $minor, $patch);
				}
			}
		}

		return null;
	}

}
