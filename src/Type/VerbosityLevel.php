<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;

final class VerbosityLevel
{

	private const TYPE_ONLY = 1;
	private const VALUE = 2;
	private const PRECISE = 3;
	private const CACHE = 4;

	/** @var self[] */
	private static array $registry;

	/**
	 * @param self::* $value
	 */
	private function __construct(private int $value)
	{
	}

	/**
	 * @param self::* $value
	 */
	private static function create(int $value): self
	{
		self::$registry[$value] ??= new self($value);
		return self::$registry[$value];
	}

	/** @return self::* */
	public function getLevelValue(): int
	{
		return $this->value;
	}

	/** @api */
	public static function typeOnly(): self
	{
		return self::create(self::TYPE_ONLY);
	}

	/** @api */
	public static function value(): self
	{
		return self::create(self::VALUE);
	}

	/** @api */
	public static function precise(): self
	{
		return self::create(self::PRECISE);
	}

	/** @api */
	public static function cache(): self
	{
		return self::create(self::CACHE);
	}

	public function isTypeOnly(): bool
	{
		return $this->value === self::TYPE_ONLY;
	}

	public function isValue(): bool
	{
		return $this->value === self::VALUE;
	}

	public function isPrecise(): bool
	{
		return $this->value === self::PRECISE;
	}

	/** @api */
	public static function getRecommendedLevelByType(Type $acceptingType, ?Type $acceptedType = null): self
	{
		$moreVerboseCallback = static function (Type $type, callable $traverse) use (&$moreVerbose): Type {
			if ($type->isCallable()->yes()) {
				$moreVerbose = true;
				return $type;
			}
			if ($type->isConstantValue()->yes() && $type->isNull()->no()) {
				$moreVerbose = true;
				return $type;
			}
			if (
				// synced with IntersectionType::describe()
				$type instanceof AccessoryNonEmptyStringType
				|| $type instanceof AccessoryNonFalsyStringType
				|| $type instanceof AccessoryLiteralStringType
				|| $type instanceof AccessoryNumericStringType
				|| $type instanceof NonEmptyArrayType
				|| $type instanceof AccessoryArrayListType
			) {
				$moreVerbose = true;
				return $type;
			}
			if ($type instanceof IntegerRangeType) {
				$moreVerbose = true;
				return $type;
			}
			return $traverse($type);
		};

		/** @var bool $moreVerbose */
		$moreVerbose = false;
		TypeTraverser::map($acceptingType, $moreVerboseCallback);

		if ($moreVerbose) {
			return self::value();
		}

		if ($acceptedType === null) {
			return self::typeOnly();
		}

		$containsInvariantTemplateType = false;
		TypeTraverser::map($acceptingType, static function (Type $type, callable $traverse) use (&$containsInvariantTemplateType): Type {
			if ($type instanceof GenericObjectType) {
				$reflection = $type->getClassReflection();
				if ($reflection !== null) {
					$templateTypeMap = $reflection->getTemplateTypeMap();
					foreach ($templateTypeMap->getTypes() as $templateType) {
						if (!$templateType instanceof TemplateType) {
							continue;
						}

						if (!$templateType->getVariance()->invariant()) {
							continue;
						}

						$containsInvariantTemplateType = true;
						return $type;
					}
				}
			}

			return $traverse($type);
		});

		if (!$containsInvariantTemplateType) {
			return self::typeOnly();
		}

		/** @var bool $moreVerbose */
		$moreVerbose = false;
		TypeTraverser::map($acceptedType, $moreVerboseCallback);

		return $moreVerbose ? self::value() : self::typeOnly();
	}

	/**
	 * @param callable(): string $typeOnlyCallback
	 * @param callable(): string $valueCallback
	 * @param callable(): string|null $preciseCallback
	 * @param callable(): string|null $cacheCallback
	 */
	public function handle(
		callable $typeOnlyCallback,
		callable $valueCallback,
		?callable $preciseCallback = null,
		?callable $cacheCallback = null,
	): string
	{
		if ($this->value === self::TYPE_ONLY) {
			return $typeOnlyCallback();
		}

		if ($this->value === self::VALUE) {
			return $valueCallback();
		}

		if ($this->value === self::PRECISE) {
			if ($preciseCallback !== null) {
				return $preciseCallback();
			}

			return $valueCallback();
		}

		if ($cacheCallback !== null) {
			return $cacheCallback();
		}

		if ($preciseCallback !== null) {
			return $preciseCallback();
		}

		return $valueCallback();
	}

}
