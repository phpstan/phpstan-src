<?php declare(strict_types = 1);

namespace Bug11761;

class ApiLimitExceededException extends \Exception
{
	/** @var string */
	protected $message = 'You have exceeded the 300 API calls per minute.';
}
