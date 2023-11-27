<?php declare(strict_types = 1);

namespace Bug9723_2;

enum State : int
{
	case StateZero = 0;
	case StateOne = 1;
	case StateTwo = 2;
}

function doFoo() {
	$state = rand(0,5);

// First time checking
// $state is 0|1|2|3|4|5
	if (
		$state === State::StateZero->value
		|| $state === State::StateTwo->value
	)
	{
		echo "No phpstan errors so far!";
	}

	switch ( $state )
	{
		case State::StateZero->value:
		case State::StateOne->value:
		case State::StateTwo->value:
			break;
		default:
			throw new Exception("Error");
	}

// Second time checking
// $state is State::StateZero->value|State::StateOne->value|State::StateTwo->value
// ... or equivalently, $state is 0|1|2
// ... but phpstan thinks $state is definitely 0
// ... and that is is being compared against 0 and 1, not 0 and 2???
	if (
		$state === State::StateZero->value
		|| $state === State::StateTwo->value
	)
	{
		echo "What's changed?";
	}
}

