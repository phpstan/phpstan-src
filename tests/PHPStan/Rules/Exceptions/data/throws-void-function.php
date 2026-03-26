<?php

namespace ThrowsVoidFunction;

class MyException extends \Exception
{

}

/**
 * @throws void
 */
function foo(): void
{
	throw new MyException();
}
