<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug11923;

use function PHPStan\Testing\assertType;

final readonly class RequestA
{
	/**
	 * @param non-empty-string $phoneNumber
	 */
	public function __construct(
		public string $phoneNumber,
		public \DateTimeImmutable $birthAt,
	) {
	}
}

final readonly class RequestB
{
	/**
	 * @param non-empty-string $passport
	 */
	public function __construct(
		public string $passport,
		public \DateTimeImmutable $birthAt,
	) {
	}
}

function testNullableTernaryMatchSubject(?object $request): void
{
	match ($request ? $request::class : null) {
		null => assertType('null', $request),
		RequestA::class => assertType(RequestA::class, $request),
		RequestB::class => assertType(RequestB::class, $request),
	};
}

function testNonNullableMatchSubject(object $request): void
{
	match ($request::class) {
		RequestA::class => assertType(RequestA::class, $request),
		RequestB::class => assertType(RequestB::class, $request),
	};
}
