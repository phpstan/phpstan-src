<?php

namespace GetClassThrowTypePhpVersions;

class Foo
{

	public function doGetClass(string $s): void
	{
		if (PHP_VERSION_ID >= 80000) {
			try {
				$a = get_class($s);
			} catch (\TypeError $e) {

			}
		} else {
			try {
				$a = get_class($s);
			} catch (\TypeError $e) {

			}
		}

		try {
			$a = get_class($s);
		} catch (\TypeError $e) {

		}
	}

}
