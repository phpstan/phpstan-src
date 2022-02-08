<?php declare(strict_types = 1);

namespace TestCleanPhpVersion;

use const PHP_VERSION_ID;

if (80100 <= PHP_VERSION_ID) {
	doFoo1();
	doFoo2();
} else {
	doBar1();
	doBar2();
}
