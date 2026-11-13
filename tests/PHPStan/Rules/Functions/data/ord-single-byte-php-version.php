<?php

namespace OrdSingleBytePhpVersion;

if (PHP_VERSION_ID < 80500) {
	ord('ab');
}

if (PHP_VERSION_ID >= 80500) {
	ord('ab');
}
