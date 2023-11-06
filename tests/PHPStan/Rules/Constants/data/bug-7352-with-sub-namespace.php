<?php

namespace Bug7352\WithSubNamespace;

const MY_OTHER_CONST = 'thing';

class World
{
	/** @var string */
	const THERE = MY_OTHER_CONST;
}
