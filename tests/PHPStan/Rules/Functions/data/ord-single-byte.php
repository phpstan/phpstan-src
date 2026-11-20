<?php declare(strict_types = 1);

namespace OrdSingleByte;

/**
 * @param 'ab'|'cde' $multiByte
 * @param ''|'ab' $emptyOrMultiByte
 * @param 'a'|'bc' $maybeSingleByte
 * @param non-empty-string $nonEmpty
 */
function doFoo(string $multiByte, string $emptyOrMultiByte, string $maybeSingleByte, string $nonEmpty, string $string): void
{
	echo ord('ab');
	echo ord("é");
	echo ord($multiByte);
	echo ord($multiByte . $string);
	echo ord($emptyOrMultiByte);
	echo ord('');

	echo ord('a');
	echo ord("\n");
	echo ord($maybeSingleByte);
	echo ord($nonEmpty);
	echo ord($string);
	echo ord($nonEmpty[0]);
}

function strictTypes(): void
{
	echo ord(12);
}
