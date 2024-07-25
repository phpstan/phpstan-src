<?php declare(strict_types = 1);

namespace PHPStan\Php;

use function floor;

/**
 * @api
 * @final
 */
class PhpVersion
{

	public const SOURCE_RUNTIME = 1;
	public const SOURCE_CONFIG = 2;
	public const SOURCE_COMPOSER_PLATFORM_PHP = 3;
	public const SOURCE_UNKNOWN = 4;

	/**
	 * @param self::SOURCE_* $source
	 */
	public function __construct(private int $versionId, private int $source = self::SOURCE_UNKNOWN)
	{
	}

	public function getSourceLabel(): string
	{
		switch ($this->source) {
			case self::SOURCE_RUNTIME:
				return 'runtime';
			case self::SOURCE_CONFIG:
				return 'config';
			case self::SOURCE_COMPOSER_PLATFORM_PHP:
				return 'config.platform.php in composer.json';
		}

		return 'unknown';
	}

	public function getVersionId(): int
	{
		return $this->versionId;
	}

	public function getVersionString(): string
	{
		$first = (int) floor($this->versionId / 10000);
		$second = (int) floor(($this->versionId % 10000) / 100);
		$third = (int) floor($this->versionId % 100);

		return $first . '.' . $second . ($third !== 0 ? '.' . $third : '');
	}

	public function supportsNullCoalesceAssign(): bool
	{
		return $this->versionId >= 70400;
	}

	public function supportsParameterContravariance(): bool
	{
		return $this->versionId >= 70400;
	}

	public function supportsReturnCovariance(): bool
	{
		return $this->versionId >= 70400;
	}

	public function supportsNoncapturingCatches(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsNativeUnionTypes(): bool
	{
		return $this->versionId >= 80000;
	}

	public function deprecatesRequiredParameterAfterOptional(): bool
	{
		return $this->versionId >= 80000;
	}

	public function deprecatesRequiredParameterAfterOptionalNullableAndDefaultNull(): bool
	{
		return $this->versionId >= 80100;
	}

	public function deprecatesRequiredParameterAfterOptionalUnionOrMixed(): bool
	{
		return $this->versionId >= 80300;
	}

	public function supportsLessOverridenParametersWithVariadic(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsThrowExpression(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsClassConstantOnExpression(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsLegacyConstructor(): bool
	{
		return $this->versionId < 80000;
	}

	public function supportsPromotedProperties(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsParameterTypeWidening(): bool
	{
		return $this->versionId >= 70200;
	}

	public function supportsUnsetCast(): bool
	{
		return $this->versionId < 80000;
	}

	public function supportsNamedArguments(): bool
	{
		return $this->versionId >= 80000;
	}

	public function throwsTypeErrorForInternalFunctions(): bool
	{
		return $this->versionId >= 80000;
	}

	public function throwsValueErrorForInternalFunctions(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsHhPrintfSpecifier(): bool
	{
		return $this->versionId >= 80000;
	}

	public function isEmptyStringValidAliasForNoneInMbSubstituteCharacter(): bool
	{
		return $this->versionId < 80000;
	}

	public function supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter(): bool
	{
		return $this->versionId >= 70200;
	}

	public function isNumericStringValidArgInMbSubstituteCharacter(): bool
	{
		return $this->versionId < 80000;
	}

	public function isNullValidArgInMbSubstituteCharacter(): bool
	{
		return $this->versionId >= 80000;
	}

	public function isInterfaceConstantImplicitlyFinal(): bool
	{
		return $this->versionId < 80100;
	}

	public function supportsFinalConstants(): bool
	{
		return $this->versionId >= 80100;
	}

	public function supportsReadOnlyProperties(): bool
	{
		return $this->versionId >= 80100;
	}

	public function supportsEnums(): bool
	{
		return $this->versionId >= 80100;
	}

	public function supportsPureIntersectionTypes(): bool
	{
		return $this->versionId >= 80100;
	}

	public function supportsCaseInsensitiveConstantNames(): bool
	{
		return $this->versionId < 80000;
	}

	public function hasStricterRoundFunctions(): bool
	{
		return $this->versionId >= 80000;
	}

	public function hasTentativeReturnTypes(): bool
	{
		return $this->versionId >= 80100;
	}

	public function supportsFirstClassCallables(): bool
	{
		return $this->versionId >= 80100;
	}

	public function supportsArrayUnpackingWithStringKeys(): bool
	{
		return $this->versionId >= 80100;
	}

	public function throwsOnInvalidMbStringEncoding(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsPassNoneEncodings(): bool
	{
		return $this->versionId < 70300;
	}

	public function producesWarningForFinalPrivateMethods(): bool
	{
		return $this->versionId >= 80000;
	}

	public function deprecatesDynamicProperties(): bool
	{
		return $this->versionId >= 80200;
	}

	public function strSplitReturnsEmptyArray(): bool
	{
		return $this->versionId >= 80200;
	}

	public function supportsDisjunctiveNormalForm(): bool
	{
		return $this->versionId >= 80200;
	}

	public function serializableRequiresMagicMethods(): bool
	{
		return $this->versionId >= 80100;
	}

	public function arrayFunctionsReturnNullWithNonArray(): bool
	{
		return $this->versionId < 80000;
	}

	// see https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.core.string-number-comparision
	public function castsNumbersToStringsOnLooseComparison(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsCallableInstanceMethods(): bool
	{
		return $this->versionId < 80000;
	}

	public function supportsJsonValidate(): bool
	{
		return $this->versionId >= 80300;
	}

	public function supportsConstantsInTraits(): bool
	{
		return $this->versionId >= 80200;
	}

	public function supportsNativeTypesInClassConstants(): bool
	{
		return $this->versionId >= 80300;
	}

	public function supportsAbstractTraitMethods(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsOverrideAttribute(): bool
	{
		return $this->versionId >= 80300;
	}

	public function supportsDynamicClassConstantFetch(): bool
	{
		return $this->versionId >= 80300;
	}

	public function supportsReadOnlyClasses(): bool
	{
		return $this->versionId >= 80200;
	}

	public function supportsReadOnlyAnonymousClasses(): bool
	{
		return $this->versionId >= 80300;
	}

	public function supportsNeverReturnTypeInArrowFunction(): bool
	{
		return $this->versionId >= 80200;
	}

	public function supportsPregUnmatchedAsNull(): bool
	{
		// while PREG_UNMATCHED_AS_NULL is defined in php-src since 7.2.x it starts working as expected with 7.4.x
		// https://3v4l.org/v3HE4
		return $this->versionId >= 70400;
	}

	public function hasDateTimeExceptions(): bool
	{
		return $this->versionId >= 80300;
	}

}
