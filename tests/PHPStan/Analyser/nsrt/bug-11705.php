<?php declare(strict_types = 1);

namespace Bug11705;

use function PHPStan\Testing\assertType;

/**
 * @param array<string,string|array<int,string>> $theInput
 * @phpstan-param array{'name':string,'owners':array<int,string>} $theInput
 * @param array<int,string> $theTags
 */
function Example(array $theInput, array $theTags): void
{
	foreach ($theTags as $tag) {
		if (!array_key_exists($tag, $theInput)) {
			continue;
		}
		switch ($tag) {
			case 'name':
				assertType("'name'", $tag);
				assertType('string', $theInput[$tag]);
				echo $theInput['name'];
				if ($tag === 'name') {
					echo "Of course it is...";
				}
				// After the always-true if, $tag should still be 'name'
				assertType("'name'", $tag);
				assertType('string', $theInput[$tag]);
				echo $theInput[$tag];
				break;
			default:
				// fall out
		}
	}
}
