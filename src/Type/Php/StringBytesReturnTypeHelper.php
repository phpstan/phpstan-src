<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;
use function count;

/**
 * Describes strings that are built out of the bytes of another string, either by
 * reordering all of them (strrev(), str_shuffle(), Random\Randomizer::shuffleBytes())
 * or by picking some of them (Random\Randomizer::getBytesFromString()).
 */
#[AutowiredService]
final class StringBytesReturnTypeHelper
{

	/**
	 * Result contains every byte of $inputType exactly once.
	 */
	public function getReorderedStringType(Type $inputType): ?Type
	{
		$accessoryTypes = [];
		if ($inputType->isNonFalsyString()->yes()) {
			$accessoryTypes[] = new AccessoryNonFalsyStringType();
		} elseif ($inputType->isNonEmptyString()->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}

		$accessoryTypes = array_merge($accessoryTypes, $this->getCaseAccessoryTypes($inputType));
		if (count($accessoryTypes) === 0) {
			return null;
		}

		return TypeCombinator::intersect(new StringType(), ...$accessoryTypes);
	}

	/**
	 * Result contains at least one byte of $inputType, each one possibly repeated.
	 * Non-falsy-ness is not preserved: picking a single byte of '10' can result in '0'.
	 */
	public function getNonEmptySelectionStringType(Type $inputType): Type
	{
		$accessoryTypes = array_merge(
			[new AccessoryNonEmptyStringType()],
			$this->getCaseAccessoryTypes($inputType),
		);

		return TypeCombinator::intersect(new StringType(), ...$accessoryTypes);
	}

	/**
	 * @return list<Type>
	 */
	private function getCaseAccessoryTypes(Type $inputType): array
	{
		$accessoryTypes = [];
		if ($inputType->isLowercaseString()->yes()) {
			$accessoryTypes[] = new AccessoryLowercaseStringType();
		}
		if ($inputType->isUppercaseString()->yes()) {
			$accessoryTypes[] = new AccessoryUppercaseStringType();
		}

		return $accessoryTypes;
	}

}
