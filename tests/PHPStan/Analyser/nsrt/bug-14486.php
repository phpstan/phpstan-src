<?php declare(strict_types = 1);

namespace Bug14486;

use function PHPStan\Testing\assertType;

function assertEmailInline(string $email): void
{
	assertType('string', $email);

	if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
		throw new \InvalidArgumentException(sprintf('Invalid email "%s".', $email));
	}

	assertType('non-falsy-string', $email);
}

function assertEmailNotEquals(string $email): void
{
	if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
		assertType('non-falsy-string', $email);
	}
}

function assertEmailTruthy(string $email): void
{
	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		assertType('non-falsy-string', $email);
	}
}

function assertEmailNegated(string $email): void
{
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return;
	}

	assertType('non-falsy-string', $email);
}

function assertIpInline(string $ip): void
{
	if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
		throw new \InvalidArgumentException('Invalid IP');
	}

	assertType('non-falsy-string', $ip);
}

function assertUrlInline(string $url): void
{
	if (false === filter_var($url, FILTER_VALIDATE_URL)) {
		throw new \InvalidArgumentException('Invalid URL');
	}

	assertType('non-falsy-string', $url);
}

function assertMacInline(string $mac): void
{
	if (false === filter_var($mac, FILTER_VALIDATE_MAC)) {
		throw new \InvalidArgumentException('Invalid MAC');
	}

	assertType('non-falsy-string', $mac);
}

function noNarrowingForDomain(string $domain): void
{
	if (false === filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
		throw new \InvalidArgumentException('Invalid domain');
	}

	// FILTER_VALIDATE_DOMAIN return type in filter map is just string, so no narrowing
	assertType('string', $domain);
}

function noNarrowingForRegexp(string $str): void
{
	if (false === filter_var($str, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '//']])) {
		throw new \InvalidArgumentException('Does not match');
	}

	// FILTER_VALIDATE_REGEXP could match empty string, no narrowing
	assertType('string', $str);
}

function noNarrowingForDefault(string $str): void
{
	if (false === filter_var($str, FILTER_DEFAULT)) {
		throw new \InvalidArgumentException('Invalid');
	}

	// FILTER_DEFAULT is not a validation filter, no narrowing
	assertType('string', $str);
}

function noNarrowingWithoutFilter(string $str): void
{
	if (filter_var($str)) {
		// No second argument, uses FILTER_DEFAULT, no narrowing
		assertType('string', $str);
	}
}

function noNarrowingInFalsyBranch(string $email): void
{
	if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
		// Filter failed, but $email could still be any string
		assertType('string', $email);
	}
}

function filterWithNullOnFailure(string $email): void
{
	$result = filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);
	assertType('non-falsy-string|null', $result);

	if ($result !== null) {
		assertType('non-falsy-string', $result);
	}
}

function noNarrowingForValidateInt(string $str): void
{
	if (filter_var($str, FILTER_VALIDATE_INT) !== false) {
		// FILTER_VALIDATE_INT returns int, not string - no string narrowing
		assertType('string', $str);
	}
}

function noNarrowingForSanitize(string $str): void
{
	if (filter_var($str, FILTER_SANITIZE_EMAIL)) {
		// FILTER_SANITIZE_EMAIL is a sanitize filter, not validation
		assertType('string', $str);
	}
}
