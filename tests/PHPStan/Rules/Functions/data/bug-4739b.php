<?php declare(strict_types = 1);

namespace Bug4739b;

function filter(callable $predicate, iterable $iterable): \Iterator {
	foreach ($iterable as $key => $value) {
		if ($predicate($value)) {
			yield $key => $value;
		}
	}
}


class Record {
	/**
	 * @var boolean
	 */
	public $isInactive;
	/**
	 * @var string
	 */
	public $name;
}

function doFoo() {
	$emails = [];
	$records = [];
	filter(
		function (Record $domain) use (&$emails): bool {
			if (!isset($emails[$domain->name])) {
				$emails[$domain->name] = TRUE;
				return TRUE;
			}
			return !$domain->isInactive;
		},
		$records
	);
	$test = (bool) mt_rand(0, 1);
	filter(
		function (bool $arg) use (&$emails): bool {
			if (empty($emails)) {
				return TRUE;
			}
			return $arg;
		},
		$records
	);
	filter(
		function (bool $arg) use ($emails): bool {
			if (empty($emails)) {
				return TRUE;
			}
			return $arg;
		},
		$records
	);
	$test = (bool) mt_rand(0, 1);
	filter(
		function (bool $arg) use (&$test): bool {
			if ($test) {
				return TRUE;
			}
			return $arg;
		},
		$records
	);
	filter(
		function (bool $arg) use ($test): bool {
			if ($test) {
				return TRUE;
			}
			return $arg;
		},
		$records
	);
}
