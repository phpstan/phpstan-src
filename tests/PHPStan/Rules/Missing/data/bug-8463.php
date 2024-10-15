<?php

namespace Bug8463;

function f1() : int
{
	while(true)
	{
		if(rand() === rand())
		{
			return 1;
		}
	}
}


function f2() : int
{
	for(;;)
	{
		if(rand() === rand())
		{
			return 1;
		}
	}
}
