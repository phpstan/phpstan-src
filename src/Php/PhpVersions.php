<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\Analyser\ConstantResolver;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use function count;
use function is_int;

/**
 * Range-aware PHP version check that handles version uncertainty.
 *
 * Unlike PhpVersion (which represents a single known version), PhpVersions wraps
 * a Type representing the possible PHP versions. When the exact version is known,
 * queries return Yes/No. When a range of versions is possible, queries return Maybe.
 *
 * This is the return type of Scope::getPhpVersion().
 *
 * @api
 */
#[ReferencedByTurboExtension(key: 'phpVersions')]
final class PhpVersions
{

	// PHP 8.0 compares a number with a non-numeric string as strings, PHP 8.4 adds BcMath\Number
	private const LOOSE_COMPARISON_PHP_VERSION_BOUNDARIES = [80000, 80400];

	public function __construct(
		private Type $phpVersions,
	)
	{
	}

	public function getType(): Type
	{
		return $this->phpVersions;
	}

	/**
	 * One PHP version for each range of the versions that loosely compares (`==`) the same way.
	 *
	 * @return non-empty-list<PhpVersion>
	 */
	public function getLooseComparisonVersions(): array
	{
		$versionIds = $this->phpVersions->getConstantScalarValues();
		if (count($versionIds) === 1 && is_int($versionIds[0])) {
			return [new PhpVersion($versionIds[0])];
		}

		$mins = [ConstantResolver::PHP_MIN_ANALYZABLE_VERSION_ID, ...self::LOOSE_COMPARISON_PHP_VERSION_BOUNDARIES];
		$versions = [];
		foreach ($mins as $i => $min) {
			$segment = IntegerRangeType::fromInterval($min, isset($mins[$i + 1]) ? $mins[$i + 1] - 1 : null);
			if ($segment->isSuperTypeOf($this->phpVersions)->no()) {
				continue;
			}

			$versions[] = new PhpVersion($min);
		}
		if ($versions === []) {
			// no PHP version reaches this code, any of them will do
			return [new PhpVersion($mins[0])];
		}

		return $versions;
	}

	public function supportsNoncapturingCatches(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function producesWarningForFinalPrivateMethods(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsNamedArguments(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsNamedArgumentAfterUnpackedArgument(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsNativeTypesInClassConstants(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsConstantsInTraits(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80200, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsNeverReturnTypeInArrowFunction(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80200, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsArrayUnpackingWithStringKeys(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsPropertyHooks(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsFinalProperties(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsAsymmetricVisibility(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsAsymmetricVisibilityForStaticProperties(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsOverrideAttributeOnProperty(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsAttributesOnGlobalConstants(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsUnsetCast(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(null, 79999)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsTrueAndFalseStandaloneType(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80200, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function throwsTypeErrorForInternalFunctions(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function throwsValueErrorForInternalFunctions(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsMaxMemoryLimit(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsThrowExpression(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsClassConstantOnExpression(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsPromotedProperties(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsNativeUnionTypes(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsFinalConstants(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function throwsOnStringCast(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(70400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsReadOnlyProperties(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsReadonlyPropertyReinitializationOnClone(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsFirstClassCallables(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsPartialFunctionApplication(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80600, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsReadOnlyClasses(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80200, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsDynamicClassConstantFetch(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsReadOnlyAnonymousClasses(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsFinalPromotedProperties(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsVoidCast(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesNonStandardCasts(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesBacktickOperator(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesDecOnNonNumericString(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesIncOnNonNumericString(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesImplicitlyFloatConversionToInt(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesNullArrayOffset(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesRequiredParameterAfterOptional(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesRequiredParameterAfterOptionalNullableAndDefaultNull(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesRequiredParameterAfterOptionalUnionOrMixed(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesImplicitlyNullableParameterTypes(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function hasTentativeReturnTypes(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function serializableRequiresMagicMethods(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsAbstractTraitMethods(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsCaseInsensitiveConstantNames(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(null, 79999)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsDeprecatedTraits(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function arrayFunctionsReturnNullWithNonArray(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(null, 79999)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function hasDateTimeExceptions(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function hasFilterThrowOnFailureConstant(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function hasPDOSubclasses(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function hasStricterRoundFunctions(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function highlightStringDoesNotReturnFalse(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function isEmptyStringValidAliasForNoneInMbSubstituteCharacter(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(null, 79999)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function isNullValidArgInMbSubstituteCharacter(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function isZeroValidCodePointInMbSubstituteCharacter(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	/** On PHP 8.0+ the Reflection* classes extended by the BetterReflection enum adapters declare the correct return types. */
	public function hasCorrectReflectionEnumAdapterReturnTypes(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function isNumericStringValidArgInMbSubstituteCharacter(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(null, 79999)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function strSplitReturnsEmptyArray(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80200, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function substrReturnFalseInsteadOfEmptyString(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(null, 79999)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(70200, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsHhPrintfSpecifier(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsPassNoneEncodings(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(null, 70299)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function throwsOnInvalidMbStringEncoding(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function throwsValueErrorForInvalidRoundingMode(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	/**
	 * PHP 8.3 rejects a negative step on an increasing range and a NAN step, builds a character
	 * range from two single bytes and no longer turns an integral float step into floats.
	 */
	public function hasStricterRangeFunction(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function warnsOnContinueTargetingSwitch(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(70300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function restrictsGlobalsModification(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesArrayPointerFunctionsOnObjects(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesStaticTraitMemberAccess(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesMbstringPseudoEncodings(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80200, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesGetClassWithoutArguments(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80300, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesTriggerErrorWithUserError(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesPowerOfZeroWithNegativeExponent(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function deprecatesCsvEscapeParameterDefault(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80400, null)->isSuperTypeOf($this->phpVersions)->result;
	}

}
