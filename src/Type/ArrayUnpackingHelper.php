<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function count;
use const PHP_INT_MAX;

/**
 * Resolves which keys of the surrounding array literal an unpacked item (`[...$a]`) takes up.
 *
 * Unpacking always renumbers integer keys, while string keys keep their name since PHP 8.1,
 * so an unpacked item takes up as many implicit indices as the unpacked value has integer keys.
 */
#[AutowiredService]
final class ArrayUnpackingHelper
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	/**
	 * How many implicit indices an unpacked value takes up, or null when it cannot be determined.
	 */
	public function getImplicitIndexCount(Type $type): ?int
	{
		$keepStringKeys = $this->phpVersion->supportsArrayUnpackingWithStringKeys();

		$constantArrays = $type->getConstantArrays();
		if (count($constantArrays) === 0) {
			if ($keepStringKeys && $this->hasOnlyStringKeys($type)) {
				return 0;
			}

			return null;
		}

		$count = null;
		foreach ($constantArrays as $constantArray) {
			if (!$constantArray->isUnsealed()->no()) {
				return null;
			}

			$integerKeysCount = 0;
			foreach ($constantArray->getKeyTypes() as $i => $keyType) {
				if ($constantArray->isOptionalKey($i)) {
					return null;
				}

				if ($keepStringKeys && $keyType->isString()->yes()) {
					continue;
				}

				$integerKeysCount++;
			}

			if ($count !== null && $count !== $integerKeysCount) {
				return null;
			}

			$count = $integerKeysCount;
		}

		return $count;
	}

	/**
	 * Keys an unpacked value contributes to the surrounding array literal, or null when
	 * they cannot be determined.
	 *
	 * @param int|null $nextImplicitIndex Null when PHP_INT_MAX was already used and no further implicit index exists
	 *
	 * @return list<ConstantIntegerType|ConstantStringType>|null
	 */
	public function getKeyTypes(Type $type, ?int $nextImplicitIndex): ?array
	{
		$keepStringKeys = $this->phpVersion->supportsArrayUnpackingWithStringKeys();

		$constantArrays = $type->getConstantArrays();
		if (count($constantArrays) !== 1) {
			if (count($constantArrays) === 0 && $keepStringKeys && $this->hasOnlyStringKeys($type)) {
				// unknown string keys don't take up any implicit index
				return [];
			}

			return null;
		}

		$constantArray = $constantArrays[0];
		if (!$constantArray->isUnsealed()->no()) {
			return null;
		}

		$keyTypes = [];
		foreach ($constantArray->getKeyTypes() as $i => $keyType) {
			if ($constantArray->isOptionalKey($i)) {
				return null;
			}

			if ($keepStringKeys && $keyType->isString()->yes()) {
				$constantStrings = $keyType->getConstantStrings();
				if (count($constantStrings) !== 1) {
					return null;
				}

				$keyTypes[] = $constantStrings[0];
				continue;
			}

			if ($nextImplicitIndex === null) {
				return null;
			}

			$keyTypes[] = new ConstantIntegerType($nextImplicitIndex);
			$nextImplicitIndex = $nextImplicitIndex === PHP_INT_MAX ? null : $nextImplicitIndex + 1;
		}

		return $keyTypes;
	}

	private function hasOnlyStringKeys(Type $type): bool
	{
		return $type->isIterable()->yes() && $type->getIterableKeyType()->isString()->yes();
	}

}
