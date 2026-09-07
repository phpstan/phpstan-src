<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug10290;

abstract class Result{
	/**
	 * @template T
	 * @param Ok<T> $ok
	 * @return T
	 */
	public static function getOk(Ok $ok) { return $ok->data; }

	/**
	 * @template E
	 * @param Err<E> $err
	 * @return E
	 */
	public static function getErr(Err $err) { return $err->data; }
}
/** @template T */
final readonly class Ok extends Result {
	/** @param T $data */
	public function __construct(protected mixed $data) {}
}
/** @template E */
final readonly class Err extends Result {
	/** @param E $data */
	public function __construct(protected mixed $data) {}
}

/**
 * @return Ok<non-empty-string>|Err<array<mixed>>
 */
function f(string $json): Result
{
	$data = json_decode($json, true, JSON_THROW_ON_ERROR);
	assert(is_array($data));

	if (isset($data['has_error']) && $data['has_error']) {
		\PHPStan\dumpType($data);
		return new Err($data);
	}

	$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
	if ($email === false) {
		\PHPStan\dumpType($data);
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
