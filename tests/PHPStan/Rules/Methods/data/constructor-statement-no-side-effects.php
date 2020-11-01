<?php

namespace ConstructorStatementNoSideEffects;

function () {
	new \Exception();
	throw new \Exception();
};

function () {
	new \PDOStatement();
	new \stdClass();
};
