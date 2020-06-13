<?php

namespace TestSingleFileSourceLocator;

class AFoo
{

}

function doFoo()
{

}

define('TestSingleFileSourceLocator\\SOME_CONSTANT', 1);

const ANOTHER_CONSTANT = 2;

if (false) {
	class InCondition
	{

	}
} elseif (true) {
	class InCondition extends AFoo
	{

	}
} else {
	class InCondition extends \stdClass
	{

	}
}
