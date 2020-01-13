<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use Nette\Utils\Strings;

/**
 * Get information about PHP printf() format strings.
 */
class PrintfFormatStringHelper
{

	/** @var string */
	private $format;

	/** @var array<int,array{string,int,string}> */
	private $placeholders;

	public function __construct(string $format)
	{
		$this->format = $format;
	}

	/**
	 * Get the number of arguments required to format the string.
	 */
	public function getNumberOfRequiredArguments(): int
	{
		$placeholders = $this->getPlaceholders();

		return $placeholders === [] ? 0 : max(array_column($placeholders, 1));
	}

	/**
	 * Get the placeholders in the format string.
	 *
	 * The result is an array of placeholders each an array containing 3 elements:
	 * 0: Format specifier (underscore is used to denote an invalid specifier)
	 * 1: Argument number (from 1)
	 * 2: The placeholder from starting percent to specifier
	 *
	 * @return array<int,array{string,int,string}>
	 */
	public function getPlaceholders(): array
	{
		if ($this->placeholders === null) {
			$this->placeholders = $this->parsePlaceholders();
		}

		return $this->placeholders;
	}

	/**
	 * @return array<int,array{string,int,string}>
	 */
	private function parsePlaceholders(): array
	{
		$formatLength = strlen($this->format);
		$pos = 0;

		$currentArgument = 0;
		$argumentNumber = 0;
		$placeholders = [];

		for ($pos = 0; $pos <= $formatLength; $pos++) {
			$nextPos = strpos($this->format, '%', $pos);

			// https://github.com/php/php-src/blob/e30f52b919e04d3a057b6759e7d66c01da550bf8/ext/standard/formatted_print.c#L418
			if ($nextPos === false) {
				break;
			}

			$pos = $nextPos;

			\assert($this->format[$pos] === '%');
			$pos++;

			// Hit the end of the string at the starting percent.
			if ($pos === $formatLength) {
				$argumentNumber = $currentArgument++;
				$placeholders[] = ['_', $argumentNumber, substr($this->format, $nextPos, $pos - $nextPos)];
				break;
			}

			// https://github.com/php/php-src/blob/e30f52b919e04d3a057b6759e7d66c01da550bf8/ext/standard/formatted_print.c#L429
			if ($this->format[$pos] === '%') {
				continue;
			}

			// https://github.com/php/php-src/blob/e30f52b919e04d3a057b6759e7d66c01da550bf8/ext/standard/formatted_print.c#L443
			if (Strings::match($this->format[$pos], '/^[a-zA-Z]$/') !== null) {
				$argumentNumber = $currentArgument++;
				$placeholders[] = [$this->format[$pos], $argumentNumber, substr($this->format, $nextPos, $pos - $nextPos + 1)];
			} else {
				// Argument... https://github.com/php/php-src/blob/e30f52b919e04d3a057b6759e7d66c01da550bf8/ext/standard/formatted_print.c#L447
				if (null !== $matches = Strings::match(substr($this->format, $pos), '/^([0-9]+)\$/')) {
					$argumentNumber = ((int) $matches[1]) - 1;
					$pos += strlen($matches[0]);
				} else {
					$argumentNumber = $currentArgument++;
				}

				// Modifiers... https://github.com/php/php-src/blob/e30f52b919e04d3a057b6759e7d66c01da550bf8/ext/standard/formatted_print.c#L465
				while (null !== $matches = Strings::match(substr($this->format, $pos), '/^([0 ]|-|\+|\'.)/')) {
					$pos += strlen($matches[0]);
				}

				// Width... https://github.com/php/php-src/blob/e30f52b919e04d3a057b6759e7d66c01da550bf8/ext/standard/formatted_print.c#L491
				if (null !== $matches = Strings::match(substr($this->format, $pos), '/^([0-9]+)/')) {
					$pos += strlen($matches[0]);
				}

				// Precision... https://github.com/php/php-src/blob/e30f52b919e04d3a057b6759e7d66c01da550bf8/ext/standard/formatted_print.c#L506
				if (null !== $matches = Strings::match(substr($this->format, $pos), '/^\.([0-9]+)?/')) {
					$pos += strlen($matches[0]);
				}

				// No idea what this is about...
				// https://github.com/php/php-src/blob/e30f52b919e04d3a057b6759e7d66c01da550bf8/ext/standard/formatted_print.c#L533
				if (($this->format[$pos] ?? '') === 'l') {
					$pos++;
				}

				// https://github.com/php/php-src/blob/e30f52b919e04d3a057b6759e7d66c01da550bf8/ext/standard/formatted_print.c#L540
				switch ($this->format[$pos] ?? '') {
					case 's':
					case 'd':
					case 'u':
					case 'g':
					case 'G':
					case 'e':
					case 'E':
					case 'f':
					case 'F':
					case 'c':
					case 'o':
					case 'x':
					case 'X':
					case 'b':
						$placeholders[] = [$this->format[$pos], $argumentNumber, substr($this->format, $nextPos, $pos - $nextPos + 1)];
						break;

					case '%':
					default:
						$placeholders[] = ['_', $argumentNumber, substr($this->format, $nextPos, $pos - $nextPos + 1)];
						break;
				}
			}
		}

		$placeholders = array_map(static function (array $placeholder): array {
			// Up to this point the arguments have been zero indexed.
			$placeholder[1]++;

			return $placeholder;
		}, $placeholders);

		return $placeholders;
	}

}
