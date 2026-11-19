<?php declare(strict_types=1);

namespace Bug15534;

function isEnabled(): bool
{
    return (bool) ($GLOBALS['enabled'] ?? false);
}

function isVisible(): bool
{
    return (bool) ($GLOBALS['visible'] ?? false);
}

function otherEnabled(): bool
{
    return (bool) ($GLOBALS['other'] ?? false);
}

function getSavedPost(string $key): ?string
{
    if (!isset($_SESSION[$key]) || !is_string($_SESSION[$key])) {
        return null;
    }

    return (string) $_SESSION[$key];
}

function translate(string $key): string
{
    return $key;
}

$post = [];

if (getSavedPost('field0')) {
    $post['field0'] = getSavedPost('field0');
}
if (getSavedPost('field1')) {
    $post['field1'] = getSavedPost('field1');
}

if (otherEnabled()) {
    if (getSavedPost('other0')) {
        $post['other0'] = getSavedPost('other0');
    }
}

if (isEnabled()) {
    if (getSavedPost('company_address')) {
        $post['company_address'] = getSavedPost('company_address');
    }
}

if (isVisible() && !isEnabled()) {
    echo 'other';
}

if (isVisible() && !otherEnabled()) {
    echo translate(isEnabled() ? 'enabled' : 'disabled');
}

if (isEnabled()) {
    echo $post['company_address'] ?? '';
}

