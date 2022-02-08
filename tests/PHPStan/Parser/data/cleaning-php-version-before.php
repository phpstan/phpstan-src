<?php declare(strict_types = 1);

namespace TestCleanPhpVersion;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID >= 80100) {
	doFoo1();
	doFoo2();
} else {
	doBar1();
	doBar2();
}
