<?php // lint < 8.0

declare(strict_types=1);

namespace VersionComparePHP7;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param string  $string
	 * @param '<'|'>' $unionValid
	 * @param '<'|'a' $unionBoth
	 * @param 'a'|'b' $unionInvalid
	 */
	public function fgetss(
		string $string,
		string $unionValid,
		string $unionBoth,
		string $unionInvalid,
	) : void
	{
		assertType('(bool|null)', \version_compare($string, $string, $string));

		assertType('false', \version_compare('Foo','Bar','<'));
		assertType('(bool|null)', \version_compare('Foo','Bar', $string));
		assertType('false', \version_compare('Foo','Bar', $unionValid));
		assertType('false|null', \version_compare('Foo','Bar', $unionBoth));
		assertType('null', \version_compare('Foo','Bar', $unionInvalid));
	}
}
