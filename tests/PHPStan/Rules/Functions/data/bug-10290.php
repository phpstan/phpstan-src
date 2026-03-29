<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug10290;

/** @template T */
abstract class Result
{
}

/** @template T */
final readonly class Ok extends Result
{
	/** @param T $data */
	public function __construct(public mixed $data)
	{
	}
}

/** @template E */
final readonly class Err extends Result
{
	/** @param E $data */
	public function __construct(public mixed $data)
	{
	}
}

/**
 * @return Ok<non-empty-string>|Err<array<mixed>>
 */
function f(string $json): Result
{
	$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	assert(is_array($data));

	if (isset($data['has_error']) && $data['has_error']) {
		return new Err($data);
	}

	$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
	if ($email === false) {
		return new Err($data);
	}

	return new Ok($email);
}

/**
 * @return Ok<true>|Err<string>
 */
function g(): Result
{
	if (rand() === 1) {
		return new Ok(true);
	}

	return new Err('error');
}
