# apiref.phpstan.org infrastructure (CDK)

CDK app that defines the AWS infrastructure for [apiref.phpstan.org](https://apiref.phpstan.org)
— the auto-generated ApiGen reference for the PHPStan codebase. Private S3 bucket
with OAC, CloudFront distribution, CloudFront Function 2.0 for per-version
landing-page redirects, Response Headers Policy, ACM cert, and the IAM role
assumed by `apiref.yml` via OIDC.

Same shape as the main-site infra at [`phpstan-dist`/website/infra](https://github.com/phpstan/phpstan/tree/2.2.x/website/infra).

## Stacks

| Stack | Resources |
| --- | --- |
| `PhpstanApirefOidcRoles` | `phpstan-apiref-infra-deploy` role (used by `apiref-infra.yml`). Reuses the account-wide OIDC provider — does NOT create a new one. |
| `PhpstanApirefWebsite` | S3 bucket (OAC, private, versioned), CloudFront distribution, CF Function 2.0, Response Headers Policy, ACM cert, `phpstan-apiref-deploy` role used by `apiref.yml`. |

Region: `us-east-1` (required for CloudFront + ACM).

## The `productionAlias` flag

Defined in `cdk.json` under `context`. Currently `true` — the distribution
carries `apiref.phpstan.org` as its alias and uses the CDK-issued ACM cert.

It exists for the original cutover (it was `false` for the first deploy so the
distribution could be created while the legacy `E37G1C2KWNAPBD` still owned the
alias). It should stay `true`; only set it back to `false` if you ever need to
detach the alias for a rebuild.

## Out-of-band resources

The Route 53 records for `apiref.phpstan.org` are **not** managed by CDK — they
were created directly via `change-resource-record-sets` during the cutover, and
CloudFormation can't UPSERT records that already exist outside its state. If the
distribution's CloudFront domain ever changes (e.g. a recreate), update the
`apiref.phpstan.org` A/AAAA alias records by hand. Same pattern as apex/www on
the main site.

## GitHub repo variables

Set under Settings → Secrets and variables → Actions → Variables in `phpstan/phpstan-src`:

| Variable | Value | Used by |
|---|---|---|
| `APIREF_INFRA_DEPLOY_ROLE_ARN` | `InfraDeployRoleArn` output of `PhpstanApirefOidcRoles` | `apiref-infra.yml` |
| `APIREF_DEPLOY_ROLE_ARN` | `DeployRoleArn` output of `PhpstanApirefWebsite` | `apiref.yml` |
| `APIREF_BUCKET` | `phpstan-apiref-web` | `apiref.yml` |
| `APIREF_DISTRIBUTION_ID` | `DistributionId` output of `PhpstanApirefWebsite` | `apiref.yml` |

## Local development

```sh
npm ci
npm run check     # tsc --noEmit
npm test          # vitest: 25 redirect-fn tests + 11 stack assertions
npm run synth     # cdk synth --all
npm run diff      # cdk diff --all (needs AWS creds for the target account)
```

Changes merged to `2.2.x` under `apigen/infra/**` are deployed automatically by
`.github/workflows/apiref-infra.yml`.
