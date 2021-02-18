<?php declare(strict_types=1);

namespace Fputcsv;

class Person
{
}

class PersonWithToString
{
	public function __toString(): string
	{
		return "to string name";
	}
}

class CsvWriter
{
	/** @param resource $handle */
	public function writeCsv($handle): void
	{
		// These are all valid scalers
		fputcsv($handle, [
			"string",
			1,
			3.5,
			true,
			false,
			null, // Yes this is accepted by fputcsv,
		]);

		// Arrays can have string for keys (which are ignored)
		fputcsv($handle, ["foo" => "bar",]);

		fputcsv($handle, [new Person,]); // Problem on this line

		// This is valid. PersonWithToString should be cast to string by fputcsv
		fputcsv($handle, [new PersonWithToString()]);
	}
}
