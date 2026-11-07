<?php declare(strict_types = 1);

namespace Bug10666;

use function PHPStan\Testing\assertType;

class Foo
{
	public function text(int $maxNbChars = 200): string
	{
		$text = [];
		while (empty($text)) {
			$size = 0;
			while ($size < $maxNbChars) {
				$text[] = 'x';
				$size++;
			}

			assertType("list<'x'>", $text);
			array_pop($text);
			assertType("list<'x'>", $text);
		}

		return implode('', $text);
	}
}
