<?php

namespace ContinueTargetingSwitchPhpVersion;

function doFoo(int $i): void
{
	if (PHP_VERSION_ID < 70300) {
		switch ($i) {
			case 1:
				continue;
		}
	}

	if (PHP_VERSION_ID >= 70300) {
		switch ($i) {
			case 1:
				continue;
		}
	}
}
