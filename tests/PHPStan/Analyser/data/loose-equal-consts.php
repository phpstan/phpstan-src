<?php declare(strict_types=1);

namespace LooseEqualConsts;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function vars($mixed): void
	{
		if ($mixed === 'backend') {
			assertType("'backend'", $mixed);
		}

		if ($mixed == 'backend') {
			assertType("'backend'", $mixed);
		}
		if ($mixed == 500) {
			assertType("500", $mixed);
		}
		if ('backend' == $mixed) {
			assertType("'backend'", $mixed);
		}
		if (500 == $mixed) {
			assertType("500", $mixed);
		}
		if ($mixed == 'backend' || $mixed == 'frontend') {
			assertType("'backend'|'frontend'", $mixed);
		}

		if ($mixed == '1') {
			assertType("mixed", $mixed);
		}
		if ($mixed == '0') {
			assertType("mixed", $mixed);
		}
		if ($mixed == 1) {
			assertType("mixed", $mixed);
		}
		if ($mixed == 0) {
			assertType("mixed", $mixed);
		}

		// equality on floats is not usefull
		if ($mixed == 1.0) {
			assertType("mixed", $mixed);
		}
		if ($mixed == 0.0) {
			assertType("mixed", $mixed);
		}
		if ($mixed == 123.0) {
			assertType("mixed", $mixed);
		}

		if ($mixed == true) {
			assertType("mixed~0|0.0|''|'0'|array{}|false|null", $mixed);
		}
		if ($mixed == false) {
			assertType("0|0.0|''|'0'|array{}|false|null", $mixed);
		}
		if ($mixed == null) {
			assertType("0|0.0|''|'0'|array{}|false|null", $mixed);
		}

		if ($mixed == []) {
			assertType("mixed", $mixed);
		}

		if ($mixed !== 'backend') {
			assertType("mixed~'backend'", $mixed);
		}
		if ($mixed != 'backend') {
			assertType('mixed', $mixed);
		}
	}

	public function constants(): void
	{
		if (APP_NAME === 'backend') {
			assertType("'backend'", APP_NAME);
		}
		if (APP_NAME == 'backend') {
			assertType("'backend'", APP_NAME);
		}
		if (APP_NAME !== 'backend') {
			assertType('*ERROR*', APP_NAME);
		}
		if (APP_NAME != 'backend') {
			assertType('*ERROR*', APP_NAME);
		}
	}
}
