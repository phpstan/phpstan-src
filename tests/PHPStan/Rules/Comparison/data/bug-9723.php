<?php declare(strict_types = 1);

namespace Bug9723;

enum State : int
{
	case StateZero = 0;
	case StateOne = 1;
}

function doFoo() {
	$state = rand(0,5);

// First time checking
// $state is 0|1|2|3|4|5
	if ( $state === State::StateZero->value )
	{
		echo "No phpstan errors so far!";
	}

	switch ( $state )
	{
		case State::StateZero->value:
		case State::StateOne->value:
			break;
		default:
			throw new Exception("Error");
	}

// Second time checking
// $state is State::StateZero->value|State::StateOne->value
// ... or equivalently, $state is 0|1
// ... but phpstan thinks $state is definitely 0
	if ( $state === State::StateZero->value )
	{
		echo "What's changed?";
	}
}

