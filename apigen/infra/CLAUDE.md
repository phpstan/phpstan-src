# apiref.phpstan.org infrastructure (CDK)

AWS CDK app (TypeScript) that defines the production infra for
[apiref.phpstan.org](https://apiref.phpstan.org) — the auto-generated ApiGen
reference for the PHPStan codebase. S3 origin, CloudFront distribution, edge
function for per-version landing-page redirects, security headers policy, ACM
cert, and the IAM roles that GitHub Actions assumes via OIDC.

See `README.md` for the bootstrap, cutover, and cleanup runbook.

This stack mirrors the main-site infra at
[`phpstan-dist`/website/infra](https://github.com/phpstan/phpstan/tree/2.3.x/website/infra)
— same patterns, same conventions; reach for that repo first when looking for
prior art.

## Stacks

Both stacks deploy to `us-east-1` (required for CloudFront + ACM).

| Stack | Defined in | Resources |
| --- | --- | --- |
| `PhpstanApirefOidcRoles` | `lib/oidc-roles-stack.ts` | `phpstan-apiref-infra-deploy` IAM role used by `apiref-infra.yml`. **Reuses** the account-wide OIDC provider — does NOT create a new one (IAM rejects duplicates of the same provider URL). |
| `PhpstanApirefWebsite` | `lib/apiref-stack.ts` | Private S3 bucket (OAC, versioned), CloudFront distribution, CF Function 2.0, Response Headers Policy, DNS-validated ACM cert for `apiref.phpstan.org`, and `phpstan-apiref-deploy` IAM role used by `apiref.yml`. |

`bin/infra.ts` is the CDK app entrypoint. It hard-codes the account/region/repo/zone constants and reads one CDK context flag, `productionAlias`, that toggles whether `apiref.phpstan.org` is attached to the distribution.

## The `productionAlias` flag

Defined in `cdk.json` under `context`, default `false`.

- `false` (pre-cutover): distribution has no aliases and no ACM cert attached. CloudFormation can create the distribution without conflict even while the legacy `E37G1C2KWNAPBD` still owns the alias. The distribution serves on its `*.cloudfront.net` domain for pre-cutover testing.
- `true` (post-cutover): distribution carries `apiref.phpstan.org` and uses the ACM cert.

The CDK code generates `Aliases: null` and `ViewerCertificate: null` when `productionAlias: false`. CloudFormation treats both as absent.

## Out-of-band resources

The Route 53 record for `apiref.phpstan.org` is **not** managed by CDK. It was
created/updated out-of-band during the cutover (raw `change-resource-record-sets`),
and CloudFormation cannot UPSERT a record that already exists outside its own
state. Same pattern as apex/www on the main site.

## Edge function

`functions/apiref-version-redirects.js` is the CloudFront Function 2.0 source.
It's a lookup-table version of the legacy `apiref-phpstan-org-viewer-request`
JS 1.0 function — same job: 301-redirect bare version URIs (e.g. `/2.2.x` or
`/2.2.x/`) to that version's landing page (`<version>/namespace-PHPStan.html`),
and `/` to the current "latest" (2.3.x).

302 → 301 was an intentional change to match the main site's redirects.

The lookup table `VERSION_REDIRECTS` is hand-curated. When a new release branch
is added (say 2.3.x), append three entries: `'/2.3.x'`, `'/2.3.x/'`, both
mapping to `/2.3.x/namespace-PHPStan.html`. If 2.3.x should become the new
latest, also update the `'/'` entry. Then `npm test` ensures the lookup table
size and `/` mapping stay in sync.

The file ends with `if (typeof module !== 'undefined') module.exports = {...}`
so it can be imported by Node-based unit tests. In the CloudFront runtime
`module` is undefined, so the export is silently skipped.

## Project layout

```
apigen/infra/
├── bin/infra.ts              # CDK app entrypoint — wires both stacks
├── lib/
│   ├── oidc-roles-stack.ts   # IAM role (reuses existing OIDC provider)
│   └── apiref-stack.ts       # everything that serves traffic
├── functions/
│   └── apiref-version-redirects.js  # CloudFront Function 2.0 source
├── test/
│   ├── apiref-version-redirects.test.ts   # Vitest: 25 redirect cases
│   └── apiref-stack.test.ts                # Vitest: 11 CDK assertions
├── cdk.json                  # CDK config + context (incl. productionAlias)
├── package.json
├── tsconfig.json
├── vitest.config.ts
├── README.md                 # bootstrap + cutover runbook (human-facing)
└── CLAUDE.md                 # this file
```

## Conventions

Same as the main-site infra:

- **Tabs for indentation** in TS, JSON, and JS files.
- **2-space indent** for YAML workflows.
- **Pin GitHub Actions to commit SHAs** with the version in a trailing comment — matches the repo style and what `step-security/harden-runner` audits.
- **No `module.exports` / ESM imports in `functions/*.js`** — they run in the CloudFront Function runtime, not Node. The only allowed exception is the `typeof module` guard for unit-test interop.
- Resource IDs in CDK use **PascalCase**. Resource *names* (`bucketName`, `roleName`, `functionName`, `responseHeadersPolicyName`) use **kebab-case** with the `phpstan-apiref-` prefix so they're easy to spot in the console.
- Output exports use the `PhpstanApiref…` prefix.

## Commands

```sh
npm ci             # install (run after pulling)
npm run check      # tsc --noEmit
npm test           # vitest run — 36 tests (redirect fn + stack assertions)
npm run synth      # cdk synth --all (no AWS creds needed)
npm run diff       # cdk diff --all (needs AWS creds for the target account)
npm run deploy     # cdk deploy --all
```

`npm test` is the gate before any deploy — the CI workflow runs `check` + `test` + `synth` in a `test` job and blocks `diff` and `deploy` on it via `needs: test`.

## CI

`.github/workflows/apiref-infra.yml` triggers on PRs and pushes that touch
`apigen/infra/**` or the workflow file itself. Three jobs (same as the main
site's `website-infra.yml`):

1. `test` — `npm ci && npm run check && npm test && npx cdk synth --all` (no AWS creds).
2. `diff` (needs: `test`) — assumes `APIREF_INFRA_DEPLOY_ROLE_ARN` via OIDC, runs `cdk diff --all`, posts a sticky PR comment.
3. `deploy` (needs: `[test, diff]`, only on push to `2.3.x`) — assumes the same role, runs `cdk deploy --all --require-approval never`.

The `apiref.yml` workflow (the actual content deploy) uses `paths-ignore` via the inline `!apigen/infra/**` form so infra-only edits don't kick off a (slow) ApiGen rebuild.

## When to edit what

- **New release branch** (need a `/X.Y.x` → `/X.Y.x/namespace-PHPStan.html` redirect) → add three entries to `VERSION_REDIRECTS` in `functions/apiref-version-redirects.js` plus three test cases in `test/apiref-version-redirects.test.ts`. If it's the new latest, update `'/'` too.
- **Changing security headers** → `lib/apiref-stack.ts` (`responseHeadersPolicy` block), not the function.
- **Adding cache behaviors or new functions** → `lib/apiref-stack.ts`. Extend `test/apiref-stack.test.ts`.
- **Changing the trust policy** (e.g. allowing another branch to deploy) → `lib/oidc-roles-stack.ts` for infra deploys, or `lib/apiref-stack.ts` for the content deploy role.
- **Cutover flag** → `cdk.json` `context.productionAlias`. Only flip after the cutover script has done its work.

## What lives elsewhere

- The ApiGen tool, theme, and PHP filters — `../` (`apigen/apigen.neon`, `apigen/src/`, `apigen/theme/`).
- The PHP source code that ApiGen reads — `../../src/`.
- The build + publish pipeline — `.github/workflows/apiref.yml`.
- The main-site (`phpstan.org`) infra — separate repo `phpstan/phpstan` (the "dist" repo), under `website/infra/`. Identical patterns; consult it first when wondering "how did we solve X for the main site?".
