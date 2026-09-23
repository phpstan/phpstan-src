<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;

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

	public function __construct(
		private Type $phpVersions,
	)
	{
	}

	public function getType(): Type
	{
		return $this->phpVersions;
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

	public function supportsReadOnlyProperties(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsFirstClassCallables(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
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

	public function supportsDeprecatedTraits(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function hasFilterThrowOnFailureConstant(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsHhPrintfSpecifier(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	/**
	 * PHPStan's BetterReflection adapters keep their narrowed native return types only on PHP 8+;
	 * the downgraded PHP 7 build widens them back to the core Reflection ones.
	 */
	public function supportsNativeReflectionAdapterReturnTypes(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

}
