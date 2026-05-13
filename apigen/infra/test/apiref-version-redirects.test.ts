import { describe, expect, it } from 'vitest';
// eslint-disable-next-line @typescript-eslint/no-require-imports
const { handler, VERSION_REDIRECTS } = require('../functions/apiref-version-redirects.js');

interface CfEvent {
	request: {
		uri: string;
		method?: string;
	};
}

function event(uri: string): CfEvent {
	return { request: { uri, method: 'GET' } };
}

describe('apiref-version-redirects handler', () => {
	describe('version landing-page redirects', () => {
		const cases: Array<[string, string]> = [
			['/', '/2.2.x/namespace-PHPStan.html'],
			['/2.2.x', '/2.2.x/namespace-PHPStan.html'],
			['/2.2.x/', '/2.2.x/namespace-PHPStan.html'],
			['/2.1.x', '/2.1.x/namespace-PHPStan.html'],
			['/2.1.x/', '/2.1.x/namespace-PHPStan.html'],
			['/2.0.x', '/2.0.x/namespace-PHPStan.html'],
			['/2.0.x/', '/2.0.x/namespace-PHPStan.html'],
			['/1.12.x', '/1.12.x/namespace-PHPStan.html'],
			['/1.12.x/', '/1.12.x/namespace-PHPStan.html'],
			['/1.11.x', '/1.11.x/namespace-PHPStan.html'],
			['/1.11.x/', '/1.11.x/namespace-PHPStan.html'],
			['/1.10.x', '/1.10.x/namespace-PHPStan.html'],
			['/1.10.x/', '/1.10.x/namespace-PHPStan.html'],
			['/1.9.x', '/1.9.x/namespace-PHPStan.html'],
			['/1.9.x/', '/1.9.x/namespace-PHPStan.html'],
		];

		for (const [uri, location] of cases) {
			it(`${uri} -> 301 ${location}`, () => {
				const result = handler(event(uri));
				expect(result.statusCode).toBe(301);
				expect(result.statusDescription).toBe('Moved Permanently');
				expect(result.headers.location.value).toBe(location);
			});
		}

		it('exposes the same lookup table to tests via module.exports', () => {
			expect(VERSION_REDIRECTS['/']).toBe('/2.2.x/namespace-PHPStan.html');
			expect(Object.keys(VERSION_REDIRECTS)).toHaveLength(cases.length);
		});

		it('latest mapping points to 2.2.x (the post-migration default)', () => {
			expect(VERSION_REDIRECTS['/']).toBe(VERSION_REDIRECTS['/2.2.x']);
		});
	});

	describe('pass-throughs', () => {
		const passThrough = [
			'/2.2.x/namespace-PHPStan.html',
			'/2.2.x/PHPStan/Analyser.html',
			'/2.2.x/some/deep/path.html',
			'/assets/style.css',
			'/1.8.x',           // 1.8.x is intentionally not in the redirect table
			'/1.8.x/',
			'/random',
			'/random/path',
		];

		for (const uri of passThrough) {
			it(`${uri} passes through unchanged`, () => {
				const result = handler(event(uri));
				expect(result.statusCode).toBeUndefined();
				expect(result.uri).toBe(uri);
			});
		}
	});
});
