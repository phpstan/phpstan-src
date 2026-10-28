<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;

/**
 * Where a native PHP type declaration is written, and for which PHP version.
 *
 * Passed to Type::toNativePhpTypeNode(). Instead of exposing the position itself,
 * it answers what the position allows, e.g. isCallableAllowed() is false for
 * properties and class constants, and isNullAllowed() is false for a standalone
 * type before PHP 8.2.
 *
 * Compound types pass a derived position to their members: toUnionMember() for each
 * member of `A|B`, toNullableInner() for `A` in `?A`, toIntersectionMember() for each
 * member of `A&B`. That is how e.g. an intersection type knows it can only be part
 * of a union (DNF) since PHP 8.2.
 *
 * ```php
 * $type->toNativePhpTypeNode(NativeTypePosition::returnType(new PhpVersion(80100)));
 * ```
 *
 * @api
 */
final class NativeTypePosition
{

	private const PARAMETER = 1;
	private const RETURN = 2;
	private const PROPERTY = 3;
	private const CLASS_CONSTANT = 4;

	private const STANDALONE = 1;
	private const UNION_MEMBER = 2;
	private const NULLABLE_INNER = 3;
	private const INTERSECTION_MEMBER = 4;

	/**
	 * @param self::PARAMETER|self::RETURN|self::PROPERTY|self::CLASS_CONSTANT $declaration
	 * @param self::STANDALONE|self::UNION_MEMBER|self::NULLABLE_INNER|self::INTERSECTION_MEMBER $context
	 */
	private function __construct(
		private int $declaration,
		private int $context,
		private PhpVersion $phpVersion,
	)
	{
	}

	/** @api */
	public static function parameter(PhpVersion $phpVersion): self
	{
		return new self(self::PARAMETER, self::STANDALONE, $phpVersion);
	}

	/** @api */
	public static function returnType(PhpVersion $phpVersion): self
	{
		return new self(self::RETURN, self::STANDALONE, $phpVersion);
	}

	/** @api */
	public static function property(PhpVersion $phpVersion): self
	{
		return new self(self::PROPERTY, self::STANDALONE, $phpVersion);
	}

	/** @api */
	public static function classConstant(PhpVersion $phpVersion): self
	{
		return new self(self::CLASS_CONSTANT, self::STANDALONE, $phpVersion);
	}

	/** A member of a union type, e.g. `A` in `A|B`. */
	public function toUnionMember(): self
	{
		return new self($this->declaration, self::UNION_MEMBER, $this->phpVersion);
	}

	/** The inner type of a nullable type, e.g. `A` in `?A`. */
	public function toNullableInner(): self
	{
		return new self($this->declaration, self::NULLABLE_INNER, $this->phpVersion);
	}

	/** A member of an intersection type, e.g. `A` in `A&B`. */
	public function toIntersectionMember(): self
	{
		return new self($this->declaration, self::INTERSECTION_MEMBER, $this->phpVersion);
	}

	/** Class names, also `self`, `parent`, and `Closure`. */
	public function isClassNameAllowed(): bool
	{
		return $this->isTypeDeclarationAllowed();
	}

	/** `int`, `float`, `string` and `bool`. */
	public function isScalarAllowed(): bool
	{
		return $this->isKeywordAllowed() && $this->isAtLeast(70000);
	}

	public function isArrayAllowed(): bool
	{
		return $this->isKeywordAllowed();
	}

	public function isIterableAllowed(): bool
	{
		return $this->isKeywordAllowed() && $this->isAtLeast(70100);
	}

	public function isCallableAllowed(): bool
	{
		return $this->isKeywordAllowed()
			&& ($this->declaration === self::PARAMETER || $this->declaration === self::RETURN);
	}

	public function isObjectAllowed(): bool
	{
		return $this->isKeywordAllowed() && $this->isAtLeast(70200);
	}

	public function isStaticAllowed(): bool
	{
		return $this->isKeywordAllowed()
			&& $this->declaration === self::RETURN
			&& $this->isAtLeast(80000);
	}

	public function isVoidAllowed(): bool
	{
		return $this->isTypeDeclarationAllowed()
			&& $this->context === self::STANDALONE
			&& $this->declaration === self::RETURN
			&& $this->isAtLeast(70100);
	}

	public function isNeverAllowed(): bool
	{
		return $this->isTypeDeclarationAllowed()
			&& $this->context === self::STANDALONE
			&& $this->declaration === self::RETURN
			&& $this->isAtLeast(80100);
	}

	public function isMixedAllowed(): bool
	{
		return $this->isTypeDeclarationAllowed()
			&& $this->context === self::STANDALONE
			&& $this->phpVersion->supportsNativeMixed();
	}

	public function isNullAllowed(): bool
	{
		if (!$this->isTypeDeclarationAllowed()) {
			return false;
		}

		if ($this->context === self::UNION_MEMBER) {
			return $this->phpVersion->supportsNativeUnionTypes();
		}

		return $this->context === self::STANDALONE && $this->phpVersion->supportsTrueFalseNullStandaloneType();
	}

	/** `false` in a union with another non-null type since PHP 8.0, otherwise since PHP 8.2. */
	public function isFalseAllowed(): bool
	{
		if (!$this->isKeywordAllowed()) {
			return false;
		}

		if ($this->context === self::UNION_MEMBER) {
			return $this->phpVersion->supportsNativeUnionTypes();
		}

		return $this->phpVersion->supportsTrueFalseNullStandaloneType();
	}

	public function isTrueAllowed(): bool
	{
		return $this->isKeywordAllowed() && $this->phpVersion->supportsTrueFalseNullStandaloneType();
	}

	/** `?A` */
	public function isNullableAllowed(): bool
	{
		return $this->isTypeDeclarationAllowed()
			&& $this->context === self::STANDALONE
			&& $this->isAtLeast(70100);
	}

	/** `A|B` */
	public function isUnionAllowed(): bool
	{
		return $this->isTypeDeclarationAllowed()
			&& $this->context === self::STANDALONE
			&& $this->phpVersion->supportsNativeUnionTypes();
	}

	/** `A&B`, also as a member of a union, `(A&B)|C`, since PHP 8.2 */
	public function isIntersectionAllowed(): bool
	{
		if (!$this->isTypeDeclarationAllowed()) {
			return false;
		}

		if ($this->context === self::UNION_MEMBER) {
			return $this->phpVersion->supportsDisjunctiveNormalForm();
		}

		return $this->context === self::STANDALONE && $this->phpVersion->supportsPureIntersectionTypes();
	}

	private function isTypeDeclarationAllowed(): bool
	{
		if ($this->declaration === self::RETURN) {
			return $this->isAtLeast(70000);
		}

		if ($this->declaration === self::PROPERTY) {
			return $this->isAtLeast(70400);
		}

		if ($this->declaration === self::CLASS_CONSTANT) {
			return $this->phpVersion->supportsNativeTypesInClassConstants();
		}

		return true;
	}

	/** members of an intersection can only be class names */
	private function isKeywordAllowed(): bool
	{
		return $this->isTypeDeclarationAllowed() && $this->context !== self::INTERSECTION_MEMBER;
	}

	private function isAtLeast(int $versionId): bool
	{
		return $this->phpVersion->getVersionId() >= $versionId;
	}

}
