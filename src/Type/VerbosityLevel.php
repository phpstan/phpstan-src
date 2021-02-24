<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;

class VerbosityLevel
{

	private const TYPE_ONLY = 1;
	private const VALUE = 2;
	private const PRECISE = 3;
	private const CACHE = 4;

	/** @var self[] */
	private static array $registry;

	private int $value;

	private function __construct(int $value)
	{
		$this->value = $value;
	}

	private static function create(int $value): self
	{
		self::$registry[$value] = self::$registry[$value] ?? new self($value);
		return self::$registry[$value];
	}

	public static function typeOnly(): self
	{
		return self::create(self::TYPE_ONLY);
	}

	public static function value(): self
	{
		return self::create(self::VALUE);
	}

	public static function precise(): self
	{
		return self::create(self::PRECISE);
	}

	public static function cache(): self
	{
		return self::create(self::CACHE);
	}

	public static function getRecommendedLevelByType(Type $acceptingType, ?Type $acceptedType = null): self
	{
		$moreVerboseCallback = static function (Type $type, callable $traverse) use (&$moreVerbose): Type {
			if ($type->isCallable()->yes()) {
				$moreVerbose = true;
				return $type;
			}
			if ($type instanceof ConstantType && !$type instanceof NullType) {
				$moreVerbose = true;
				return $type;
			}
			if ($type instanceof AccessoryNumericStringType) {
				$moreVerbose = true;
				return $type;
			}
			if ($type instanceof NonEmptyArrayType) {
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
	 * @return string
	 */
	public function handle(
		callable $typeOnlyCallback,
		callable $valueCallback,
		?callable $preciseCallback = null,
		?callable $cacheCallback = null
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

		if ($this->value === self::CACHE) {
			if ($cacheCallback !== null) {
				return $cacheCallback();
			}

			if ($preciseCallback !== null) {
				return $preciseCallback();
			}

			return $valueCallback();
		}

		throw new \PHPStan\ShouldNotHappenException();
	}

}
