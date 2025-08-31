<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use Nette\Utils\Strings;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use function array_filter;
use function array_keys;
use function count;
use function in_array;
use function max;
use function sprintf;
use function strlen;
use const PREG_SET_ORDER;

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

	/** @phpstan-return array<int, non-empty-list<PrintfPlaceholder>> parameter index => placeholders */
	public function getPrintfPlaceholders(string $format): array
	{
		return $this->parsePlaceholders(self::PRINTF_SPECIFIER_PATTERN, $format);
	}

	public function getScanfPlaceholdersCount(string $format): int
	{
		return $this->getPlaceholdersCount('(?<specifier>[cdDeEfinosuxX%s]|\[[^\]]+\])', $format);
	}

	/** @phpstan-return array<int, non-empty-list<PrintfPlaceholder>> parameter index => placeholders */
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
		$parsedPlaceholders = [];
		$parameterIdx = 0;
		$placeholderNumber = 0;

		foreach ($placeholders as $placeholder) {
			$placeholderNumber++;
			$showValueSuffix = false;

			if (isset($placeholder['width']) && $placeholder['width'] !== '') {
				$parsedPlaceholders[] = new PrintfPlaceholder(
					sprintf('"%s" (width)', $placeholder[0]),
					$parameterIdx++,
					$placeholderNumber,
					'strict-int',
				);
				$showValueSuffix = true;
			}

			if (isset($placeholder['precision']) && $placeholder['precision'] !== '') {
				$parsedPlaceholders[] = new PrintfPlaceholder(
					sprintf('"%s" (precision)', $placeholder[0]),
					$parameterIdx++,
					$placeholderNumber,
					'strict-int',
				);
				$showValueSuffix = true;
			}

			$parsedPlaceholders[] = new PrintfPlaceholder(
				sprintf('"%s"', $placeholder[0]) . ($showValueSuffix ? ' (value)' : ''),
				isset($placeholder['position']) && $placeholder['position'] !== ''
					? $placeholder['position'] - 1
					: $parameterIdx++,
				$placeholderNumber,
				$this->getAcceptingTypeBySpecifier($placeholder['specifier'] ?? ''),
			);
		}

		foreach ($parsedPlaceholders as $placeholder) {
			$result[$placeholder->parameterIndex][] = $placeholder;
		}

		return $result;
	}

	/** @phpstan-return 'string'|'int'|'float'|'mixed' */
	private function getAcceptingTypeBySpecifier(string $specifier): string
	{
		if ($specifier === 's') {
			return 'string';
		}

		if (in_array($specifier, ['d', 'u', 'c', 'o', 'x', 'X', 'b'], true)) {
			return 'int';
		}

		if (in_array($specifier, ['e', 'E', 'f', 'F', 'g', 'G', 'h', 'H'], true)) {
			return 'float';
		}

		return 'mixed';
	}

	private function getPlaceholdersCount(string $specifiersPattern, string $format): int
	{
		$paramIndices = array_keys($this->parsePlaceholders($specifiersPattern, $format));

		return $paramIndices === []
			? 0
			// The indices start from 0
			: max($paramIndices) + 1;
	}

}
