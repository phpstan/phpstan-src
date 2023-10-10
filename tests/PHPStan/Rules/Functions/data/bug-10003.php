<?php declare(strict_types = 1);

namespace Bug10003;

class HelloWorld
{
 	public function check(\MongoDB\Driver\Monitoring\Subscriber $a): void
	{
		// Wrong.
		\MONGODB\Driver\Monitoring\addSubscriber($a);
		// Correct.
		\MongoDB\Driver\Monitoring\addSubscriber($a);
		// Wrong.
		\mongodb\driver\monitoring\addsubscriber($a);
	}
}
