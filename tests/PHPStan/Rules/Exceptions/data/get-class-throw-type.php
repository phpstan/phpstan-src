<?php

namespace GetClassThrowType;

class Foo
{

	/**
	 * @param \stdClass $phpDocObject
	 * @param mixed $mixed
	 */
	public function doFoo(\stdClass $o, $phpDocObject, $mixed): void
	{
		try {
			$a = get_class($o);
		} catch (\TypeError $e) {

		}

		try {
			$a = get_class($this);
		} catch (\TypeError $e) {

		}

		try {
			$a = get_class();
		} catch (\Error $e) {

		}

		$f = function (): string {
			try {
				return get_class();
			} catch (\Error $e) {
				return '';
			}
		};

		try {
			$a = get_class($phpDocObject);
		} catch (\TypeError $e) {

		}

		try {
			$a = get_class($mixed);
		} catch (\TypeError $e) {

		}

		if (is_object($mixed)) {
			try {
				$a = get_class($mixed);
			} catch (\TypeError $e) {

			}
		}
	}

}

function doBar(): void
{
	try {
		$a = get_class();
	} catch (\Error $e) {

	}
}
