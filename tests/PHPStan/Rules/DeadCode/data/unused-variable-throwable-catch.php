<?php

namespace UnusedVariableThrowableCatch;

interface Session
{

	public function getDisplayName(): string;

	/** @throws \DomainException */
	public function handleEncoded(string $packet): void;

}

function receive(Session $session, string $packet): void
{
	$name = $session->getDisplayName();
	try {
		$session->handleEncoded($packet);
	} catch (\DomainException $e) {
		echo $e->getMessage();
	} catch (\Throwable $e) {
		echo "Crash occurred while handling a packet from session: $name";
		throw $e;
	}
}

function receiveNested(Session $session, string $packet): void
{
	$name = $session->getDisplayName();
	try {
		try {
			$session->handleEncoded($packet);
		} catch (\DomainException $e) {
			echo $e->getMessage();
		}
	} catch (\Throwable $e) {
		echo "Crash occurred while handling a packet from session: $name";
		throw $e;
	}
}
