<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\DependencyInjection\AutowiredService;
use function floor;

/**
 * @api
 */
#[AutowiredService(factory: '@PHPStan\Php\PhpVersionFactory::create')]
final class PhpVersion
{

	public const SOURCE_RUNTIME = 1;
	public const SOURCE_CONFIG = 2;
	public const SOURCE_COMPOSER_PLATFORM_PHP = 3;
	public const SOURCE_UNKNOWN = 4;

	/**
	 * @api
	 *
	 * @param self::SOURCE_* $source
	 */
	public function __construct(private int $versionId, private int $source = self::SOURCE_UNKNOWN)
	{
	}

	/**
	 * @return self::SOURCE_*
	 */
	public function getSource(): int
	{
		return $this->source;
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

	public function getMajorVersionId(): int
	{
		return (int) floor($this->versionId / 10000);
	}

	public function getMinorVersionId(): int
	{
		return (int) floor(($this->versionId % 10000) / 100);
	}

	public function getPatchVersionId(): int
	{
		return (int) floor($this->versionId % 100);
	}

	public function getVersionString(): string
	{
		$first = $this->getMajorVersionId();
		$second = $this->getMinorVersionId();
		$third = $this->getPatchVersionId();

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

	public function nonNumericStringAndIntegerIsFalseOnLooseComparison(): bool
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

	public function supportsPregCaptureOnlyNamedGroups(): bool
	{
		// https://php.watch/versions/8.2/preg-n-no-capture-modifier
		return $this->versionId >= 80200;
	}

	public function supportsPropertyHooks(): bool
	{
		return $this->versionId >= 80400;
	}

	public function supportsFinalProperties(): bool
	{
		return $this->versionId >= 80400;
	}

	public function supportsAsymmetricVisibility(): bool
	{
		return $this->versionId >= 80400;
	}

	public function supportsAsymmetricVisibilityForStaticProperties(): bool
	{
		return $this->versionId >= 80500;
	}

	public function supportsLazyObjects(): bool
	{
		return $this->versionId >= 80400;
	}

	public function hasDateTimeExceptions(): bool
	{
		return $this->versionId >= 80300;
	}

	public function isCurloptUrlCheckingFileSchemeWithOpenBasedir(): bool
	{
		// Before PHP 8.0, when setting CURLOPT_URL, an unparsable URL or a file:// scheme would fail if open_basedir is used
		// https://github.com/php/php-src/blob/php-7.4.33/ext/curl/interface.c#L139-L158
		// https://github.com/php/php-src/blob/php-8.0.0/ext/curl/interface.c#L128-L130
		return $this->versionId < 80000;
	}

	/**
	 * whether curl handles are represented as 'resource' or CurlShareHandle
	 */
	public function supportsCurlShareHandle(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsCurlSharePersistentHandle(): bool
	{
		return $this->versionId >= 80500;
	}

	public function highlightStringDoesNotReturnFalse(): bool
	{
		return $this->versionId >= 80400;
	}

	public function deprecatesImplicitlyNullableParameterTypes(): bool
	{
		return $this->versionId >= 80400;
	}

	public function substrReturnFalseInsteadOfEmptyString(): bool
	{
		return $this->versionId < 80000;
	}

	public function supportsBcMathNumberOperatorOverloading(): bool
	{
		return $this->versionId >= 80400;
	}

	public function hasPDOSubclasses(): bool
	{
		return $this->versionId >= 80400;
	}

	public function deprecatesImplicitlyFloatConversionToInt(): bool
	{
		return $this->versionId >= 80100;
	}

	public function deprecatesNullArrayOffset(): bool
	{
		return $this->versionId >= 80500;
	}

	public function supportsFinalPromotedProperties(): bool
	{
		return $this->versionId >= 80500;
	}

	public function supportsVoidCast(): bool
	{
		return $this->versionId >= 80500;
	}

	public function supportsNoDiscardAttribute(): bool
	{
		return $this->versionId >= 80500;
	}

	public function deprecatesNonStandardCasts(): bool
	{
		return $this->versionId >= 80500;
	}

	public function deprecatesBacktickOperator(): bool
	{
		return $this->versionId >= 80500;
	}

	public function supportsAttributesOnGlobalConstants(): bool
	{
		return $this->versionId >= 80500;
	}

	public function supportsDeprecatedTraits(): bool
	{
		return $this->versionId >= 80500;
	}

	public function supportsOverrideAttributeOnProperty(): bool
	{
		return $this->versionId >= 80500;
	}

	public function deprecatesDecOnNonNumericString(): bool
	{
		return $this->versionId >= 80300;
	}

	public function deprecatesIncOnNonNumericString(): bool
	{
		return $this->versionId >= 80500;
	}

}
