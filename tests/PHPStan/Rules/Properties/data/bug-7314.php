<?php // lint >= 8.1

namespace Bug7314;

class UserId1
{
	public function __construct(
		public readonly int $id,
	) {
	}
}

trait HasId
{
	public function __construct(
		public readonly int $id,
	) {
	}
}
class UserId2
{
	use HasId;
}

