<?php

namespace Bug5642;

class Foo
{
	public function test(\Couchbase\BucketManager $manager)
	{
		$manager->flush();
		$manager->flush('');
		$manager->flush('', '');
	}
}
