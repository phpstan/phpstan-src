<?php declare(strict_types=1);

namespace Bug15534Types;

use function PHPStan\Testing\assertType;

function isEnabled(): bool { return (bool) rand(0, 1); }
function otherEnabled(): bool { return (bool) rand(0, 1); }
function getSavedPost(string $key): ?string { return rand(0, 1) === 1 ? $key : null; }

function doFoo(): void
{
	$post = [];
	if (getSavedPost('field0')) {
		$post['field0'] = getSavedPost('field0');
	}
	if (getSavedPost('field1')) {
		$post['field1'] = getSavedPost('field1');
	}
	if (otherEnabled()) {
		if (getSavedPost('other0')) {
			$post['other0'] = 'o';
		}
	}

	if (isEnabled()) {
		if (getSavedPost('c')) {
			$post['company_address'] = 'c';
		}
	}

	if (!otherEnabled()) {
		assertType("array{}|array{field0?: non-falsy-string, field1?: non-falsy-string, other0?: 'o', company_address?: 'c'}", $post);
	}
	if (isEnabled()) {
		assertType("array{}|array{field0?: non-falsy-string, field1?: non-falsy-string, other0?: 'o', company_address?: 'c'}", $post);
	}
}
