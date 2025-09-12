<?php

namespace Bug13511;

class User
{
	public function __construct(
		public string $name,
		public string $email,
	) {}
}

class Printer
{
	/** @param object{name: string, email: string, phone?: string} $object */
	function printInfo(object $object): void
	{
		return;
	}
}

function (Printer $printer, User $user): void {
	$printer->printInfo($user);
};
