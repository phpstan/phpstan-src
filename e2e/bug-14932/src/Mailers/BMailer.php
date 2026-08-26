<?php declare(strict_types = 1);

namespace Bug14932;

final class BMailer
{

	public const NAME = 'b';

	use SomeTrait;
	use DiffMessageTrait;

}
