<?php

namespace Bug13565;

class NotAString {}

/**
 * @return array{name: string}
 */
function x(): array {
	return ['name' => 'string', 'email' => new NotAString()];
}

/**
 * @return array{name: string, email?: string}
 */
function y(): array { return x(); }

function send_mail(string $val): void { echo "sending mail to $val"; }
