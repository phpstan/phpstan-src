<?php declare(strict_types = 1);

namespace Bug14177Nsrt;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param list{0: string, 1: string, 2?: string, 3?: string} $b
	 */
	public function testList(array $b): void
	{
		if (array_key_exists(3, $b)) {
			assertType('array{string, string, string, string}', $b);
		} else {
			assertType('array{0: string, 1: string, 2?: string}', $b);
		}
		assertType('list{0: string, 1: string, 2?: string, 3?: string}', $b);
	}

	public function placeholderToEditor(string $html): void
	{
		$result = preg_replace_callback(
			'~\[image\\sid="(\\d+)"(?:\\shref="([^"]*)")?(?:\\sclass="([^"]*)")?\]~',
			function (array $matches): string {
				$id = (int) $matches[1];

				assertType('list{0: non-falsy-string, 1: decimal-int-string, 2?: string, 3?: string}', $matches);

				$replacement = sprintf(
					'<img src="%s"%s/>',
					$id,
					array_key_exists(3, $matches) ? sprintf(' class="%s"', $matches[3]) : '',
				);

				assertType('list{0: non-falsy-string, 1: decimal-int-string, 2?: string, 3?: string}', $matches);

				return array_key_exists(2, $matches) && $matches[2] !== ''
					? sprintf('<a href="%s">%s</a>', $matches[2], $replacement)
					: $replacement;
			},
			$html,
		);
	}

	public function placeholderToEditor2(string $html): void
	{
		$result = preg_replace_callback(
			'~\[image\\sid="(\\d+)?"(?:\\shref="([^"]*)")?(?:\\sclass="([^"]*)")?\]~',
			function (array $matches): string {
				$id = (int) $matches[0];

				assertType('list{0: non-falsy-string, 1?: \'\'|decimal-int-string, 2?: string, 3?: string}', $matches);

				$replacement = sprintf(
					'<img src="%s"%s/>',
					$id,
					array_key_exists(2, $matches) ? sprintf(' class="%s"', $matches[2]) : '',
				);

				assertType('list{0: non-falsy-string, 1?: \'\'|decimal-int-string, 2?: string, 3?: string}', $matches);

				return array_key_exists(1, $matches) && $matches[1] !== ''
					? sprintf('<a href="%s">%s</a>', $matches[1], $replacement)
					: $replacement;
			},
			$html,
		);
	}
}

class HelloWorld2
{
	/**
	 * @param list{0: string, 1: string, 2?: string, 3?: string} $b
	 */
	public function testUnset0OnList(array $b): void
	{
		assertType('true', array_is_list($b));
		unset($b[0]);
		assertType('false', array_is_list($b));
		$b[] = 'foo';
		assertType('false', array_is_list($b));
	}

	/**
	 * @param list{0: string, 1: string, 2?: string, 3?: string} $b
	 */
	public function testUnset1OnList(array $b): void
	{
		assertType('true', array_is_list($b));
		unset($b[1]);
		assertType('false', array_is_list($b));
		$b[] = 'foo';
		assertType('false', array_is_list($b));
	}

	/**
	 * @param list{0: string, 1: string, 2?: string, 3?: string} $b
	 */
	public function testUnset2OnList(array $b): void
	{
		assertType('true', array_is_list($b));
		unset($b[2]);
		assertType('bool', array_is_list($b));
		$b[] = 'foo';
		assertType('bool', array_is_list($b));
	}

	/**
	 * @param list{0: string, 1: string, 2?: string, 3?: string} $b
	 */
	public function testUnset3OnList(array $b): void
	{
		assertType('true', array_is_list($b));
		unset($b[3]);
		assertType('bool', array_is_list($b));
		$b[] = 'foo';
		assertType('bool', array_is_list($b));
	}

	/**
	 * @param array{0: string, 1?: string, 2: string, 3?: string} $b
	 */
	public function testUnset0OnArray(array $b): void
	{
		assertType('bool', array_is_list($b));
		unset($b[0]);
		assertType('false', array_is_list($b));
		$b[] = 'foo';
		assertType('false', array_is_list($b));
	}

	/**
	 * @param array{0: string, 1?: string, 2: string, 3?: string} $b
	 */
	public function testUnset1OnArray(array $b): void
	{
		assertType('bool', array_is_list($b));
		unset($b[1]);
		assertType('false', array_is_list($b));
		$b[] = 'foo';
		assertType('false', array_is_list($b));
	}

	/**
	 * @param array{0: string, 1?: string, 2: string, 3?: string} $b
	 */
	public function testUnset2OnArray(array $b): void
	{
		assertType('bool', array_is_list($b));
		unset($b[2]);
		assertType('false', array_is_list($b));
		$b[] = 'foo';
		assertType('false', array_is_list($b));
	}

	/**
	 * @param array{0: string, 1?: string, 2: string, 3?: string} $b
	 */
	public function testUnset3OnArray(array $b): void
	{
		assertType('bool', array_is_list($b));
		unset($b[3]);
		assertType('bool', array_is_list($b));
		$b[] = 'foo';
		assertType('bool', array_is_list($b));
	}

	/**
	 * @param list{0: string, 1: string, 2?: string, 3?: string} $a
	 * @param list{0: string, 1?: string, 2?: string, 3?: string} $b
	 * @param list{0: string, 1?: string, 2?: string, 3: string} $c
	 * @param 1|2 $int
	 */
	public function testUnsetNonConstant(array $a, array $b, array $c, int $int): void
	{
		assertType('true', array_is_list($a));
		assertType('true', array_is_list($b));
		assertType('true', array_is_list($c));
		unset($a[$int]);
		unset($b[$int]);
		unset($c[$int]);
		assertType('false', array_is_list($a));
		assertType('bool', array_is_list($b));
		assertType('bool', array_is_list($c));
	}

	/**
	 * @param list{0?: string, 1?: string, 2?: string, 3?: string} $a
	 * @param list{0: string, 1?: string, 2?: string, 3?: string} $b
	 */
	public function testUnsetInt(array $a, array $b, array $c, int $int): void
	{
		assertType('true', array_is_list($a));
		assertType('true', array_is_list($b));
		unset($a[$int]);
		unset($b[$int]);
		assertType('bool', array_is_list($a));
		assertType('false', array_is_list($b));
	}

	/**
	 * @param list{0?: string, 1?: string, 2?: string} $l
	 */
	public function testFoo($l): void
	{
		if (array_key_exists(2, $l, true)) {
			assertType('true', array_is_list($l));
			assertType('array{string, string, string}', $l);
			if (array_key_exists(1, $l, true)) {
				assertType('true', array_is_list($l));
				assertType('array{string, string, string}', $l);
			} else {
				assertType('true', array_is_list($l));
				assertType('*NEVER*', $l);
			}
		} else {
			assertType('true', array_is_list($l));
			assertType('list{0?: string, 1?: string}', $l);
		}
	}
}
