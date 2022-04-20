<?php declare(strict_types = 1);

namespace Bug4559;

class HelloWorld
{
	public function doBar()
	{
		$response = json_decode('');
		if (isset($response->error->code)) {
			echo $response->error->message ?? '';
		}
	}
}
