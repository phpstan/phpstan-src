<?php

namespace Argon2IdPasswordHash;

function (): void {
	password_hash('my strong password', PASSWORD_ARGON2ID);
};
