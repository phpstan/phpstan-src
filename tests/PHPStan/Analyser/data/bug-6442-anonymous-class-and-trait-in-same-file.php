<?php

namespace Bug6442;

trait T
{
}

new class()
{
	use T;
};
