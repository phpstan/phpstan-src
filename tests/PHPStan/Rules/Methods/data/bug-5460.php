<?php

namespace Bug5460;

function (): void {
	$request = new \http\Client\Request("POST", "", []);
	$request->setOptions(["timeout" => 1]);

	$request->getBody()->append("");

	$client = new \http\Client();
	$client->enqueue($request)->send();

	$response = $client->getResponse($request);
};
