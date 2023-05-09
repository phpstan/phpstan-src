<?php declare(strict_types = 1);

namespace Bug4559;

class HelloWorld
{
	public function doBar(string $s)
	{
		$response = json_decode($s);
		if (isset($response->error->code)) {
			echo $response->error->message ?? '';
		}
	}
}
