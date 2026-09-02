// CloudFront Function (runtime cloudfront-js-2.0), viewer-request.
//
// Replaces the legacy `apiref-phpstan-org-viewer-request` JS 1.0 function.
// Same job: redirect bare-version URIs (e.g. `/2.2.x` or `/2.2.x/`) to that
// version's landing page. `/` redirects to the current "latest" — bumped
// from 2.1.x to 2.2.x in this migration.
//
// When a new branch lands, add three entries here: `/X.Y.x`, `/X.Y.x/`, and
// update the `/` mapping if it should become the new latest.
//
// CloudFront runs this file directly and looks for a top-level `function handler`.
// The trailing `module.exports` is gated on `typeof module` so the same source
// can be imported into Node-based unit tests; in the CF runtime `module` is not
// defined, so the export is silently skipped.

var VERSION_REDIRECTS = {
	'/':         '/2.2.x/namespace-PHPStan.html',
	'/2.3.x':    '/2.3.x/namespace-PHPStan.html',
	'/2.3.x/':   '/2.3.x/namespace-PHPStan.html',
	'/2.2.x':    '/2.2.x/namespace-PHPStan.html',
	'/2.2.x/':   '/2.2.x/namespace-PHPStan.html',
	'/2.1.x':    '/2.1.x/namespace-PHPStan.html',
	'/2.1.x/':   '/2.1.x/namespace-PHPStan.html',
	'/2.0.x':    '/2.0.x/namespace-PHPStan.html',
	'/2.0.x/':   '/2.0.x/namespace-PHPStan.html',
	'/1.12.x':   '/1.12.x/namespace-PHPStan.html',
	'/1.12.x/':  '/1.12.x/namespace-PHPStan.html',
	'/1.11.x':   '/1.11.x/namespace-PHPStan.html',
	'/1.11.x/':  '/1.11.x/namespace-PHPStan.html',
	'/1.10.x':   '/1.10.x/namespace-PHPStan.html',
	'/1.10.x/':  '/1.10.x/namespace-PHPStan.html',
	'/1.9.x':    '/1.9.x/namespace-PHPStan.html',
	'/1.9.x/':   '/1.9.x/namespace-PHPStan.html',
	// 1.8.x exists in the bucket but had no landing-page redirect in the
	// legacy function; preserve that gap.
};

function handler(event) {
	var target = VERSION_REDIRECTS[event.request.uri];
	if (target) {
		return {
			statusCode: 301,
			statusDescription: 'Moved Permanently',
			headers: {
				location: { value: target },
			},
		};
	}
	return event.request;
}

if (typeof module !== 'undefined') {
	module.exports = { handler: handler, VERSION_REDIRECTS: VERSION_REDIRECTS };
}
