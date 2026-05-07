<?php declare(strict_types = 1);

namespace TestCleanVersionCompare;

if (version_compare(PHP_VERSION, '8.1', '>=')) {
	doFoo1();
	doFoo2();
} else {
	doBar1();
	doBar2();
}
