<?php

namespace CatchUnion;

class FooException extends \Exception
{

}

class BarException extends \Exception
{

}

function () {
	try {
		maybeThrows();
	} catch (FooException | BarException $e) {
		die;
	}
};
