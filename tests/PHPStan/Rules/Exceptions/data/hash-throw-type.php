<?php

namespace HashThrowType;

class Foo
{

	/**
	 * @param 'md5' $phpDocAlgo
	 */
	public function doFoo(string $data, string $algo, string $phpDocAlgo, bool $flag): void
	{
		try {
			$a = hash('sha256', $data);
		} catch (\ValueError $e) {

		}

		try {
			$a = hash('SHA3-256', $data, true);
		} catch (\ValueError $e) {

		}

		$b = $flag ? 'md5' : 'crc32b';
		try {
			$a = hash($b, $data);
		} catch (\ValueError $e) {

		}

		try {
			$a = hash('md6', $data);
		} catch (\ValueError $e) {

		}

		try {
			$a = hash($algo, $data);
		} catch (\ValueError $e) {

		}

		try {
			$a = hash($phpDocAlgo, $data);
		} catch (\ValueError $e) {

		}
	}

}
