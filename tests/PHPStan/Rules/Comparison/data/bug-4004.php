<?php

namespace Bug4004;

function (): void {
	$pupKey = '';
	$privateR = '';

	$original = "I just wanna tell you how I'm feeling\nGotta make you understand";
	$encrypted = '';
	$decrypted = '';

	if (! @\openssl_public_encrypt($original, $encrypted, $pupKey, OPENSSL_PKCS1_OAEP_PADDING)) {
		throw new \Exception('Unable to encrypt data, invalid key provided?');
	}

	if (! @\openssl_private_decrypt($encrypted, $decrypted, $privateR, OPENSSL_PKCS1_OAEP_PADDING) || $decrypted !== $original) {
		throw new \Exception();
	}
};
