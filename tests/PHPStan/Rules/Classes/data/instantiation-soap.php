<?php

namespace TestInstantiation;

function () {
	throw new \SoapFault('Server', 123);
};

function () {
	throw new \SoapFault('Server', 'Some error message');
};

function () {
	throw new \SoapFault('Server', 'Some error message', 'actor', [], 'name', []);
};

function () {
	throw new \SoapFault('Server', 'Some error message', 'actor', 'test', 'name', 'test');
};


function () {
	throw new \SoapFault('Server', 'Some error message', 'actor', 1, 'name', 2);
};
