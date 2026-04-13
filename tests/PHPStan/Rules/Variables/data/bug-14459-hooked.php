<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug14459Hooked;

final class DtoHooked
{
	public function __construct(
		public \stdClass $policyholderId {
			set => $value;
		},
		public ?\stdClass $nullablePolicyholderId {
			set => $value;
		},
	) {}
}

function testHooked(DtoHooked $dto): \stdClass
{
	$x = $dto->policyholderId ?? new \stdClass();
	return $x;
}

function testHookedNullable(DtoHooked $dto): \stdClass
{
	$x = $dto->nullablePolicyholderId ?? new \stdClass();
	return $x;
}
