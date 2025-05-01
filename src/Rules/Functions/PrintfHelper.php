<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use Nette\Utils\Strings;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use function array_filter;
use function array_flip;
use function array_keys;
use function array_map;
use function array_reduce;
use function count;
use function max;
use function sort;
use function sprintf;
use function strlen;
use function usort;
use const PREG_SET_ORDER;

/** @phpstan-type AcceptingTypeString 'strict-int'|'int'|'float'|'string'|'mixed' */
#[AutowiredService]
final class PrintfHelper
{

	private const PRINTF_SPECIFIER_PATTERN = '(?<specifier>[bs%s]|l?[cdeEgfFGouxX])';

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getPrintfPlaceholdersCount(string $format): int
	{
		return $this->getPlaceholdersCount(self::PRINTF_SPECIFIER_PATTERN, $format);
	}

	/** @return array<int, array{string, callable(Type): bool}> position => [type name, matches callback] */
	public function getPrintfPlaceholderAcceptingTypes(string $format): array
	{
		$placeholders = $this->parsePlaceholders(self::PRINTF_SPECIFIER_PATTERN, $format);
		$result = [];
		// int can go into float, string and mixed as well.
		// float can't go into int, but it can go to string/mixed.
		// string can go into mixed, but not into int/float.
		// mixed can only go into mixed.
		$typeSequenceMap = array_flip(['int', 'float', 'string', 'mixed']);

		foreach ($placeholders as $position => $types) {
			sort($types);
			$typeNames = array_map(
				static fn (string $t) => $t === 'strict-int'
					? 'int'
					: $t,
				$types,
			);
			$typeName = array_reduce(
				$typeNames,
				static fn (string $carry, string $type) => $typeSequenceMap[$carry] < $typeSequenceMap[$type]
					? $carry
					: $type,
				'mixed',
			);
			$result[$position] = [
				$typeName,
				static function (Type $t) use ($types): bool {
					foreach ($types as $acceptingType) {
						$subresult = match ($acceptingType) {
							'strict-int' => (new IntegerType())->accepts($t, true)->yes(),
							// This allows float, constant non-numeric string, ...
							'int' => ! $t->toInteger() instanceof ErrorType,
							'float' => ! $t->toFloat() instanceof ErrorType,
							// The function signature already limits the parameters to stringable types, so there's
							// no point in checking it again here.
							'string', 'mixed' => true,
						};

						if (!$subresult) {
							return false;
						}
					}

					return true;
				},
			];
		}

		return $result;
	}

	public function getScanfPlaceholdersCount(string $format): int
	{
		return $this->getPlaceholdersCount('(?<specifier>[cdDeEfinosuxX%s]|\[[^\]]+\])', $format);
	}

	/** @phpstan-return array<int, non-empty-list<AcceptingTypeString>> position => type */
	private function parsePlaceholders(string $specifiersPattern, string $format): array
	{
		$addSpecifier = '';
		if ($this->phpVersion->supportsHhPrintfSpecifier()) {
			$addSpecifier .= 'hH';
		}

		$specifiers = sprintf($specifiersPattern, $addSpecifier);

		$pattern = '~(?<before>%*)%(?:(?<position>\d+)\$)?[-+]?(?:[ 0]|(?:\'[^%]))?(?<width>\*)?-?\d*(?:\.(?:\d+|(?<precision>\*))?)?' . $specifiers . '~';

		$matches = Strings::matchAll($format, $pattern, PREG_SET_ORDER);

		if (count($matches) === 0) {
			return [];
		}

		$placeholders = array_filter($matches, static fn (array $match): bool => strlen($match['before']) % 2 === 0);

		$result = [];
		$positionToIdxMap = [];
		$positionalPlaceholders = [];
		$idx = $position = 0;

		foreach ($placeholders as $placeholder) {
			if (isset($placeholder['width']) && $placeholder['width'] !== '') {
				$result[$idx] = ['strict-int' => 1];
				$positionToIdxMap[$position++] = $idx++;
			}

			if (isset($placeholder['precision']) && $placeholder['precision'] !== '') {
				$result[$idx] = ['strict-int' => 1];
				$positionToIdxMap[$position++] = $idx++;
			}

			if (isset($placeholder['position']) && $placeholder['position'] !== '') {
				// It may reference future position, so we have to process them later.
				$positionalPlaceholders[] = $placeholder;
				continue;
			}

			$position++;
			$positionToIdxMap[$position] = $idx;
			$result[$idx++][$this->getAcceptingTypeBySpecifier($placeholder['specifier'] ?? '')] = 1;
		}

		usort(
			$positionalPlaceholders,
			static fn (array $a, array $b) => (int) $a['position'] <=> (int) $b['position'],
		);

		foreach ($positionalPlaceholders as $placeholder) {
			$idx = $positionToIdxMap[$placeholder['position']] ?? null;

			if ($idx === null) {
				continue;
			}

			$result[$idx][$this->getAcceptingTypeBySpecifier($placeholder['specifier'] ?? '')] = 1;
		}

		return array_map(static fn (array $a) => array_keys($a), $result);
	}

	/** @phpstan-return 'string'|'int'|'float'|'mixed' */
	private function getAcceptingTypeBySpecifier(string $specifier): string
	{
		return match ($specifier) {
			's' => 'string',
			'd', 'u', 'c', 'o', 'x', 'X', 'b' => 'int',
			'e', 'E', 'f', 'F', 'g', 'G', 'h', 'H' => 'float',
			default => 'mixed',
		};
	}

	private function getPlaceholdersCount(string $specifiersPattern, string $format): int
	{
		$addSpecifier = '';
		if ($this->phpVersion->supportsHhPrintfSpecifier()) {
			$addSpecifier .= 'hH';
		}

		$specifiers = sprintf($specifiersPattern, $addSpecifier);

		$pattern = '~(?<before>%*)%(?:(?<position>\d+)\$)?[-+]?(?:[ 0]|(?:\'[^%]))?(?<width>\*)?-?\d*(?:\.(?:\d+|(?<precision>\*))?)?' . $specifiers . '~';

		$matches = Strings::matchAll($format, $pattern, PREG_SET_ORDER);

		if (count($matches) === 0) {
			return 0;
		}

		$placeholders = array_filter($matches, static fn (array $match): bool => strlen($match['before']) % 2 === 0);

		if (count($placeholders) === 0) {
			return 0;
		}

		$maxPositionedNumber = 0;
		$maxOrdinaryNumber = 0;
		foreach ($placeholders as $placeholder) {
			if (isset($placeholder['width']) && $placeholder['width'] !== '') {
				$maxOrdinaryNumber++;
			}

			if (isset($placeholder['precision']) && $placeholder['precision'] !== '') {
				$maxOrdinaryNumber++;
			}

			if (isset($placeholder['position']) && $placeholder['position'] !== '') {
				$maxPositionedNumber = max((int) $placeholder['position'], $maxPositionedNumber);
			} else {
				$maxOrdinaryNumber++;
			}
		}

		return max($maxPositionedNumber, $maxOrdinaryNumber);
	}

}
