<?php

namespace FilterInputType;

class Foo
{

	public function doFoo(int $type): void
	{
		filter_input(INPUT_GET, 'foo');
		filter_input(INPUT_POST, 'foo');
		filter_input(INPUT_COOKIE, 'foo');
		filter_input(INPUT_SERVER, 'foo');
		filter_input(INPUT_ENV, 'foo');

		filter_input(-1, 'foo');
		filter_input($type, 'foo');
	}

	public function doBar(int $type): void
	{
		filter_input_array(INPUT_GET);
		filter_input_array(INPUT_POST);
		filter_input_array(INPUT_COOKIE);
		filter_input_array(INPUT_SERVER);
		filter_input_array(INPUT_ENV);

		filter_input_array(-1);
		filter_input_array($type);
	}

}
