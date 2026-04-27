<?php

namespace Bug2940;

/**
 * @param array{
 *   foo2: string,
 *   foo1: string,
 *   foobarbar1?: string,
 *   foobarbar2?: int,
 *   foobarbar3?: string,
 *   foobarbar4: string,
 *   foobarbar6?: string,
 *   foobarbar7: int
 * } $input
 */
function foo(array $input)
{
	//stuff
}


function bar()
{
	foo(['xx'=>3]);
}

/**
 * @param array{
 *   foo2: string,
 *   foo1: string,
 *   foobarbar1?: string,
 *   foobarbar2?: int,
 *   foobarbar3?: string,
 *   foobarbar4: string,
 *   foobarbar6?: string,
 *   foobarbar7?: string,
 *   foobarbar8?: string,
 *   foobarbar9?: string,
 *   foobarbar10?: string,
 *   foobarbar11?: string,
 *   foobarbar12?: string,
 *   foobarbar13?: string,
 *   foobarbar14?: string,
 *   foobarbar15?: string,
 *   foobarbar16?: string,
 *   foobarbar17?: string,
 *   foobarbar18?: string,
 *   foobarbar19?: string,
 *   foobarbar20?: string,
 *   foobarbar21?: string,
 *   foobarbar22?: string,
 *   foobarbar23?: string,
 *   foobarbar24?: string,
 *   foobarbar25?: string,
 *   foobarbar26?: string,
 *   foobarbar27?: string,
 *   foobarbar28?: string,
 *   foobarbar29?: string,
 *   foobarbar30?: string,
 *   foobarbar31?: string,
 *   foobarbar7: int
 * } $input
 */
function foo2(array $input)
{
	//stuff
}


function bar2()
{
	foo2(['xx'=>3]);
}
