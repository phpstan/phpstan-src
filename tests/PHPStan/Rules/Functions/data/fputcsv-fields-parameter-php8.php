<?php declare(strict_types = 1);

namespace Fputcsv;


$handle = fopen("/tmp/output.csv", "w");
if ($handle === false) die();

class StringablePerson implements \Stringable {
	public function __toString(): string {
		return "stringable name";
	}
}

// This is valid. StringablePerson should be treated as a string
fputcsv($handle, [new StringablePerson()]);
