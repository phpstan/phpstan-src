<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14459;

final class Dto
{
	public function __construct(
		public readonly \stdClass $payerId,
		public readonly \stdClass $policyholderId,
		public readonly ?\stdClass $nullablePolicyholderId,
	) {}
}

final class DtoNonReadonly
{
	public function __construct(
		public \stdClass $payerId,
	) {}
}

class DtoNonPromotedReadonly
{
	public readonly \stdClass $payerId;

	public function __construct(\stdClass $payerId) {
		$this->payerId = $payerId;
	}
}

function test(Dto $dto): \stdClass
{
	$x = $dto->policyholderId ?? $dto->payerId;
	return $x;
}

function testNullable(Dto $dto): \stdClass
{
	$x = $dto->nullablePolicyholderId ?? $dto->payerId;
	return $x;
}

function testNonReadonly(DtoNonReadonly $dto): \stdClass
{
	$x = $dto->payerId ?? new \stdClass();
	return $x;
}

function testNonPromotedReadonly(DtoNonPromotedReadonly $dto): \stdClass
{
	$x = $dto->payerId ?? new \stdClass();
	return $x;
}
