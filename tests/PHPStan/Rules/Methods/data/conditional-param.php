<?php

namespace ConditionalParam;

class HelloWorld
{
	/**
	 * @param ($flags is PREG_OFFSET_CAPTURE ? bool : string) $demoArg
	 * @param int-mask<PREG_UNMATCHED_AS_NULL|PREG_OFFSET_CAPTURE> $flags
	 */
	public static function replaceCallback($demoArg, int $flags = 0): void
	{}
}

function (): void {
	HelloWorld::replaceCallback(true); // correct, error expected
	HelloWorld::replaceCallback("string"); // correct

	HelloWorld::replaceCallback(true, PREG_OFFSET_CAPTURE); // correct
	HelloWorld::replaceCallback("string", PREG_OFFSET_CAPTURE); // correct, error expected

	HelloWorld::replaceCallback(true, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL); // should not report error
};
