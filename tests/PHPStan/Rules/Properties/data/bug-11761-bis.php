<?php declare(strict_types = 1);

namespace Bug11761Bis;

class ApiLimitExceededException extends \Exception
{
	protected $message = 'You have exceeded the 300 API calls per minute.';
}
