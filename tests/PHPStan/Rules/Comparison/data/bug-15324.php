<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15324;

function doFoo(string $namespace):void {
	if (substr($namespace, 0, 4) === 'hello-world') {} // always false
	if ('hello-world' === substr($namespace, 0, 4)) {} // always false

	if (substr($namespace, 0, 13) === 'hello-world') {} // fine
	if ('hello-world' === substr($namespace, 0, 13)) {} // fine

	if (substr($namespace, 0, 11) === 'hello-world') {} // fine
	if ('hello-world' === substr($namespace, 0, 11)) {} // fine
}

/**
 * @param 'foo'|'foobar' $literals
 * @param 3|4 $length
 * @param 0|3 $zeroOrThree
 * @param -3|-4 $negativeOffset
 */
function doBar(string $s, string $literals, int $length, int $zeroOrThree, int $negativeOffset, int $int): void
{
	if (substr($s, 0, 4) !== 'hello-world') {} // always true
	if (substr($s, 2, 3) === 'abcd') {} // always false
	if (substr($s, 2, 3) === 'abc') {} // fine
	if (substr($s, -3) === 'abcd') {} // always false
	if (substr($s, -3, null) === 'abcd') {} // always false
	if (substr($s, -4) === 'abcd') {} // fine
	if (substr($s, 3) === 'abcd') {} // fine
	if (substr($s, 0, -1) === 'abcd') {} // fine
	if (substr($s, 0, $int) === 'abcd') {} // fine
	if (substr($s, 0, 0) === '') {} // fine
	if (substr($s, 0, 0) === 'a') {} // always false

	if (substr($s, 0, 2) === $literals) {} // always false
	if (substr($s, 0, 3) === $literals) {} // fine
	if (substr($s, 0, $length) === 'abcde') {} // always false
	if (substr($s, 0, $length) === 'abcd') {} // fine
	if (substr($s, 0, $zeroOrThree) === 'abcd') {} // always false
	if (substr($s, $negativeOffset) === 'abcde') {} // always false
	if (substr($s, $negativeOffset) === 'abcd') {} // fine

	if (mb_substr($s, 0, 2) === 'äöü') {} // always false
	if (mb_substr($s, 0, 3) === 'äöü') {} // fine
	if (mb_substr($s, -2) === 'äöü') {} // always false
	if (mb_substr($s, 0, 3, 'UTF-8') === 'äöü') {} // fine
	if (mb_substr($s, 0, 3, '8bit') === 'äöü') {} // always false
	if (mb_substr($s, 0, 3, 'unknown') === 'äöü') {} // fine

	if (mb_strcut($s, 0, 3) === 'abcd') {} // always false
	if (mb_strcut($s, 0, 4) === 'abcd') {} // fine
	if (mb_strcut($s, -3) === 'abcd') {} // fine

	if ($s[0] === 'ab') {} // always false
	if ($s[0] === 'a') {} // fine
	if (chr($int) === 'ab') {} // always false
	if (chr($int) === 'a') {} // fine

	if (substr(string: $s, offset: 0, length: 4) === 'hello-world') {} // not handled
	if (substr($s, 0, 4) === null) {} // always false
	if (substr($s, 0, 4) === 12345) {} // always false

}
