<?php declare(strict_types = 1); // lint >= 8.0

class TotpEnrollment
{
	public bool $confirmed;
}

class User
{
	public ?TotpEnrollment $totpEnrollment;
}

function () {
	$user = new User();

	return match ($user->totpEnrollment === null) {
		true => false,
		false => $user->totpEnrollment->confirmed,
	};
};
