<?php

namespace IllegalConstructorMethodCall;

class ExtendedDateTimeWithMethodCall extends \DateTimeImmutable
{
	public function __construct(string $datetime = "now", ?\DateTimeZone $timezone = null)
	{
		// Avoid infinite loop
		if (count(debug_backtrace()) > 1) {
			return;
		}
		$this->__construct($datetime, $timezone);
	}

	public function mutate(string $datetime = "now", ?\DateTimeZone $timezone = null): void
	{
		$this->__construct($datetime, $timezone);
	}
}

class ExtendedDateTimeWithParentCall extends \DateTimeImmutable
{
	public function __construct(string $datetime = "now", ?\DateTimeZone $timezone = null)
	{
		parent::__construct($datetime, $timezone);
	}

	public function mutate(string $datetime = "now", ?\DateTimeZone $timezone = null): void
	{
		parent::__construct($datetime, $timezone);
	}
}

class ExtendedDateTimeWithSelfCall extends \DateTimeImmutable
{
	public function __construct(string $datetime = "now", ?\DateTimeZone $timezone = null)
	{
		// Avoid infinite loop
		if (count(debug_backtrace()) > 1) {
			return;
		}
		self::__construct($datetime, $timezone);
		ExtendedDateTimeWithSelfCall::__construct($datetime, $timezone);
	}

	public function mutate(string $datetime = "now", ?\DateTimeZone $timezone = null): void
	{
		self::__construct($datetime, $timezone);
		ExtendedDateTimeWithSelfCall::__construct($datetime, $timezone);
	}
}

class Foo
{

	public function doFoo()
	{
		$extendedDateTime = new ExtendedDateTimeWithMethodCall('2022/04/12');
		$extendedDateTime->__construct('2022/04/13');
	}

}
