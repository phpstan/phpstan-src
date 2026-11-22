<?php declare(strict_types = 1);

namespace Bug15790;

// Transliterator::create() and createInverse() set the error that
// intl_get_error_code() and intl_get_error_message() return.
function createTransliterator(string $id): \Transliterator
{
	$transliterator = \Transliterator::create($id);
	if ($transliterator === null) {
		throw new \IntlException(intl_get_error_message(), intl_get_error_code());
	}

	return $transliterator;
}

/** @return never */
function failWithIntlError(string $id): void
{
	\Transliterator::create($id); // Populate intl_get_error_*()

	throw new \IntlException(intl_get_error_message(), intl_get_error_code());
}

/** @return never */
function failWithReverseError(): void
{
	// Create a failing reverse-transliterator to populate intl_get_error_*()
	\Transliterator::createFromRules('A > B')->createInverse();

	throw new \IntlException(intl_get_error_message(), intl_get_error_code());
}

// Burn the same time as a real check so a missing user cannot be told apart by timing.
function login(string $password, ?string $hash): bool
{
	if ($hash === null) {
		password_verify($password, '$2y$10$abcdefghijklmnopqrstuuJ1lYzZ3Y1Yy8s8o8s8o8s8o8s8o8s8o');

		return false;
	}

	return password_verify($password, $hash);
}
