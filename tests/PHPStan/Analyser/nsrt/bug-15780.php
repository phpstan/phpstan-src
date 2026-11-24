<?php declare(strict_types = 1);

namespace Bug15780;

use function PHPStan\Testing\assertType;

interface Bag
{
	/** @return string|int|float|bool|null */
	public function get(string $key);
	/** @return array<mixed> */
	public function all(?string $key = null): array;
	public function has(string $key): bool;
}

function doFoo(Bag $data, string $secret): void
{
	$keys = [
		'client_name' => null, 'logo_uri' => null, 'redirect_uris' => null, 'post_logout_redirect_uris' => null,
		'token_endpoint_auth_method' => ['client_secret_basic', 'client_secret_post'],
		'policy_uri' => null, 'tos_uri' => null, 'jwks_uri' => null, 'jwks' => null,
		'sector_identifier_uri' => null, 'subject_type' => ['pairwise', 'public'],
		'default_max_age' => null, 'require_auth_time' => null, 'default_acr_values' => null,
		'initiate_login_uri' => null, 'request_uris' => null, 'response_types' => null, 'scope' => null,
		'dsi_type' => ['none', 'evidence'], 'dsi_source_attributes' => [],
	];
	$params = ['client_id' => 'a', 'issued_at' => 1, 'token' => 'b', 'uri_path' => 'c'];
	if ($data->get('application_type') === 'private') {
		$params['client_secret'] = $secret;
		$params['client_role'] = 'user';
	}
	foreach ($keys as $key => $supportedValues) {
		if ($data->has($key)) {
			if (in_array($key, ['redirect_uris', 'request_uris', 'post_logout_redirect_uris', 'response_types', 'default_acr_values'])) {
				$params[$key] = implode('|', $data->all($key));
			} elseif (in_array($key, ['dsi_source_attributes'])) {
				$params[$key] = $data->all($key);
			} elseif ($key === 'jwks') {
				$params[$key] = json_encode($data->all(), JSON_THROW_ON_ERROR);
			} elseif ($key === 'jwks_uri') {
				$params[$key] = $secret;
			} else {
				$params[$key] = $data->get($key);
			}
			if (!empty($supportedValues)) {
			}
		}
	}
	unset($params['uri_path']);
	if (isset($params['jwks'])) {
		assertType('non-empty-string', $params['jwks']);
		json_decode($params['jwks'], true);
	}
}
