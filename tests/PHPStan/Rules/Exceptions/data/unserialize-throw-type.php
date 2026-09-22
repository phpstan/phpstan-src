<?php

namespace UnserializeThrowType;

class Foo
{

	/**
	 * @param array{allowed_classes: false} $phpDocOptions
	 */
	public function doFoo(string $s, array $options, array $phpDocOptions, int $depth): void
	{
		try {
			$a = unserialize($s);
		} catch (\ValueError $e) {

		}

		try {
			$a = unserialize($s);
		} catch (\TypeError $e) {

		}

		try {
			$a = unserialize($s, ['allowed_classes' => false]);
		} catch (\TypeError $e) {

		}

		try {
			$a = unserialize($s, ['allowed_classes' => [], 'max_depth' => 10]);
		} catch (\TypeError $e) {

		}

		try {
			$a = unserialize($s, ['allowed_classes' => [Foo::class], 'max_depth' => 10]);
		} catch (\ValueError $e) {

		}

		try {
			$a = unserialize($s, ['allowed_classes' => [Foo::class]]);
		} catch (\TypeError $e) {

		}

		try {
			$a = unserialize($s, ['max_depth' => -1]);
		} catch (\ValueError $e) {

		}

		try {
			$a = unserialize($s, ['allowed_classes' => ['a b']]);
		} catch (\ValueError $e) {

		}

		try {
			$a = unserialize($s, ['max_depth' => '5']);
		} catch (\TypeError $e) {

		}

		try {
			$a = unserialize($s, $options);
		} catch (\ValueError $e) {

		}

		try {
			$a = unserialize($s, $phpDocOptions);
		} catch (\TypeError $e) {

		}

		try {
			$a = unserialize($s, ['max_depth' => $depth]);
		} catch (\ValueError $e) {

		}
	}

}
