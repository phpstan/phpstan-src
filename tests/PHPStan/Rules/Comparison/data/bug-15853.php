<?php declare(strict_types = 1);

namespace Bug15853;

// reduced from symfony/console OutputFormatter::formatAndWrap()
function closingTags(string $message): int
{
	$closed = 0;
	$openTagRegex = '[a-z](?:[^\\\\<>]++ | \\\\.)*+';
	$closeTagRegex = '[a-z][^<>]*+';
	preg_match_all("#(?<!\\\\)<(($openTagRegex) | /($closeTagRegex)?)>#ix", $message, $matches, \PREG_OFFSET_CAPTURE);
	foreach ($matches[0] as $i => $match) {
		$text = $match[0];
		if ($open = '/' !== $text[1]) {
			$tag = $matches[1][$i][0];
		} else {
			$tag = $matches[3][$i][0] ?? '';
		}

		if (!$open && !$tag) { // '</>' closes the current style
			$closed++;
		}
	}

	return $closed;
}
