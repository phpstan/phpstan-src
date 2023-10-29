<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use function array_keys;
use function implode;
use function in_array;
use function preg_match;
use function preg_quote;
use function strlen;
use function substr;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @see https://github.com/composer/composer/pull/10107
 */
class PhpFileCleaner
{

	/** @var array<array{name: string, length: int, pattern: string}> */
	private array $typeConfig = [];

	private string $restPattern;

	private string $contents = '';

	private int $len = 0;

	private int $index = 0;

	public function __construct()
	{
		foreach (['class', 'interface', 'trait', 'enum'] as $type) {
			$this->typeConfig[$type[0]] = [
				'name' => $type,
				'length' => strlen($type),
				'pattern' => '{.\b(?<![\$:>])' . $type . '\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+}Ais',
			];
		}

		$this->restPattern = '{[^{}?"\'</d' . implode('', array_keys($this->typeConfig)) . ']+}A';
	}

	public function clean(string $contents, int $maxMatches): string
	{
		$this->contents = $contents;
		$this->len = strlen($contents);
		$this->index = 0;

		$inType = false;
		$typeLevel = 0;

		$inDefine = false;

		$clean = '';
		while ($this->index < $this->len) {
			$this->skipToPhp();
			$clean .= '<?';

			while ($this->index < $this->len) {
				$char = $this->contents[$this->index];
				if ($char === '?' && $this->peek('>')) {
					$clean .= '?>';
					$this->index += 2;
					continue 2;
				}

				if (in_array($char, ['"', "'"], true)) {
					if ($inDefine) {
						$clean .= $char . $this->consumeString($char);
						$inDefine = false;
					} else {
						$this->skipString($char);
						$clean .= 'null';
					}

					continue;
				}

				if ($char === '{') {
					if ($inType) {
						$typeLevel++;
					}

					$clean .= $char;
					$this->index++;
					continue;
				}

				if ($char === '}') {
					if ($inType) {
						$typeLevel--;

						if ($typeLevel === 0) {
							$inType = false;
						}
					}

					$clean .= $char;
					$this->index++;
					continue;
				}

				if ($char === '<' && $this->peek('<') && $this->match('{<<<[ \t]*+([\'"]?)([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*+)\\1(?:\r\n|\n|\r)}A', $match)) {
					$this->index += strlen($match[0]);
					$this->skipHeredoc($match[2]);
					$clean .= 'null';
					continue;
				}

				if ($char === '/') {
					if ($this->peek('/')) {
						$this->skipToNewline();
						continue;
					}
					if ($this->peek('*')) {
						$this->skipComment();
						continue;
					}
				}

				if (
					$inType
					&& $char === 'c'
					&& $this->match('~.\b(?<![\$:>])const(\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+)~Ais', $match, $this->index - 1)
				) {
					// It's invalid PHP but it does not matter
					$clean .= 'class_const' . $match[1];
					$this->index += strlen($match[0]) - 1;
					continue;
				}

				if ($char === 'd' && $this->match('~.\b(?<![\$:>])define\s*+\(~Ais', $match, $this->index - 1)) {
					$inDefine = true;
					$clean .= $match[0];
					$this->index += strlen($match[0]) - 1;
					continue;
				}

				if (isset($this->typeConfig[$char])) {
					$type = $this->typeConfig[$char];

					if (substr($this->contents, $this->index, $type['length']) === $type['name']) {
						if ($maxMatches === 1 && $this->match($type['pattern'], $match, $this->index - 1)) {
							return $clean . $match[0];
						}

						$inType = true;
					}
				}

				$this->index += 1;
				if ($this->match($this->restPattern, $match)) {
					$clean .= $char . $match[0];
					$this->index += strlen($match[0]);
				} else {
					$clean .= $char;
				}
			}
		}

		return $clean;
	}

	private function skipToPhp(): void
	{
		while ($this->index < $this->len) {
			if ($this->contents[$this->index] === '<' && $this->peek('?')) {
				$this->index += 2;
				break;
			}

			$this->index += 1;
		}
	}

	private function consumeString(string $delimiter): string
	{
		$string = '';

		$this->index += 1;
		while ($this->index < $this->len) {
			if ($this->contents[$this->index] === '\\' && ($this->peek('\\') || $this->peek($delimiter))) {
				$string .= $this->contents[$this->index];
				$string .= $this->contents[$this->index + 1];

				$this->index += 2;
				continue;
			}

			if ($this->contents[$this->index] === $delimiter) {
				$string .= $delimiter;
				$this->index += 1;
				break;
			}

			$string .= $this->contents[$this->index];
			$this->index += 1;
		}

		return $string;
	}

	private function skipString(string $delimiter): void
	{
		$this->index += 1;
		while ($this->index < $this->len) {
			if ($this->contents[$this->index] === '\\' && ($this->peek('\\') || $this->peek($delimiter))) {
				$this->index += 2;
				continue;
			}
			if ($this->contents[$this->index] === $delimiter) {
				$this->index += 1;
				break;
			}
			$this->index += 1;
		}
	}

	private function skipComment(): void
	{
		$this->index += 2;
		while ($this->index < $this->len) {
			if ($this->contents[$this->index] === '*' && $this->peek('/')) {
				$this->index += 2;
				break;
			}

			$this->index += 1;
		}
	}

	private function skipToNewline(): void
	{
		while ($this->index < $this->len) {
			if ($this->contents[$this->index] === "\r" || $this->contents[$this->index] === "\n") {
				return;
			}
			$this->index += 1;
		}
	}

	private function skipHeredoc(string $delimiter): void
	{
		$firstDelimiterChar = $delimiter[0];
		$delimiterLength = strlen($delimiter);
		$delimiterPattern = '{' . preg_quote($delimiter) . '(?![a-zA-Z0-9_\x80-\xff])}A';

		while ($this->index < $this->len) {
			// check if we find the delimiter after some spaces/tabs
			switch ($this->contents[$this->index]) {
				case "\t":
				case ' ':
					$this->index += 1;
					continue 2;
				case $firstDelimiterChar:
					if (
						substr($this->contents, $this->index, $delimiterLength) === $delimiter
						&& $this->match($delimiterPattern)
					) {
						$this->index += $delimiterLength;
						return;
					}
					break;
			}

			// skip the rest of the line
			while ($this->index < $this->len) {
				$this->skipToNewline();

				// skip newlines
				while ($this->index < $this->len && ($this->contents[$this->index] === "\r" || $this->contents[$this->index] === "\n")) {
					$this->index += 1;
				}

				break;
			}
		}
	}

	private function peek(string $char): bool
	{
		return $this->index + 1 < $this->len && $this->contents[$this->index + 1] === $char;
	}

	/**
	 * @param string[]|null $match
	 */
	private function match(string $regex, ?array &$match = null, ?int $offset = null): bool
	{
		return preg_match($regex, $this->contents, $match, 0, $offset ?? $this->index) === 1;
	}

}
