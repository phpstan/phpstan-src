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

Defined in `cdk.json` under `context`. Default `false`.

- `false`: distribution carries no aliases, no ACM cert attached (serves on the CF default `*.cloudfront.net` domain). First deploy succeeds while `apiref.phpstan.org` is still owned by the legacy distribution `E37G1C2KWNAPBD`.
- `true`: distribution carries `apiref.phpstan.org` as its alias and uses the new ACM cert. Set after the manual cutover.

## Out-of-band resources

The Route 53 record for `apiref.phpstan.org` is **not** managed by CDK. The
cutover script UPSERTs the record (via raw `change-resource-record-sets`); CDK
isn't aware of it. Same pattern as apex/www on the main site.

## Local development

```sh
npm ci
npm run check     # tsc --noEmit
npm test          # vitest: 25 redirect-fn tests + 11 stack assertions
npm run synth     # cdk synth --all
npm run diff      # cdk diff --all (needs AWS creds for the target account)
```

## One-time bootstrap

The CDK bootstrap roles for the AWS account already exist (created by the
phpstan-dist repo's CDK app). You only need to deploy the OIDC roles stack
once, from a maintainer's laptop with admin AWS credentials:

```sh
npx cdk deploy PhpstanApirefOidcRoles
```

Note the `InfraDeployRoleArn` output and set the corresponding GitHub repo
variable.

## GitHub repo variables to set (in phpstan/phpstan-src)

After the first deploys, set these under Settings → Secrets and variables → Actions → Variables:

| Variable | Value | Used by |
|---|---|---|
| `APIREF_INFRA_DEPLOY_ROLE_ARN` | `InfraDeployRoleArn` output of `PhpstanApirefOidcRoles` | `apiref-infra.yml` |
| `APIREF_DEPLOY_ROLE_ARN` | `DeployRoleArn` output of `PhpstanApirefWebsite` | `apiref.yml` |
| `APIREF_BUCKET` | `phpstan-apiref-web` | `apiref.yml` |
| `APIREF_DISTRIBUTION_ID` | `DistributionId` output of `PhpstanApirefWebsite` | `apiref.yml` |

## Cutover runbook (legacy → new)

This moves `apiref.phpstan.org` from the legacy distribution `E37G1C2KWNAPBD`
to the new CDK-managed distribution. Expect ~5–10 min of intermittent 403s on
`apiref.phpstan.org` while CloudFront edges propagate the alias swap.

**Pre-cutover (with `productionAlias: false`):**

1. Merge the PR that adds this `apigen/infra/` directory. `apiref-infra.yml` deploys both stacks.
2. Copy bucket contents: `aws s3 sync s3://web-apiref.phpstan.org/ s3://phpstan-apiref-web/` (~334 MB / 13.5k objects).
3. Smoke-test on the new distribution's CF domain (look up `DistributionDomain` output):
   ```sh
   D=$(aws cloudfront get-distribution --id <new-id> --query 'Distribution.DomainName' --output text)
   curl -sI "https://$D/"                                          # 301 to /2.2.x/namespace-PHPStan.html
   curl -sI "https://$D/2.2.x/namespace-PHPStan.html"              # 200
   curl -sI "https://$D/1.9.x"                                     # 301 to /1.9.x/namespace-PHPStan.html
   curl -sI "https://$D/" | grep -iE 'strict-transport|x-content-type|x-frame|referrer-policy|x-xss'
   ```
   Verify HSTS, XCTO, XFO=SAMEORIGIN, Referrer-Policy present; no X-XSS-Protection.

**Cutover (with `productionAlias: true`):**

The sequence (do Route 53 first — we learned the hard way on the main site that CloudFront's `AddAlias` does a DNS sanity check):

1. UPSERT Route 53 `apiref.phpstan.org` CNAME → new distribution's CF domain.
2. Wait for Route 53 INSYNC (~30–60s).
3. Detach `apiref.phpstan.org` from `E37G1C2KWNAPBD` via `aws cloudfront update-distribution`.
4. Add `apiref.phpstan.org` to the new distribution (retry every 20s if CloudFront's DNS-check cache is stale).
5. Wait for new distribution `Deployed`.
6. Smoke-test against `https://apiref.phpstan.org/`.

Then merge the PR that flips `productionAlias: true` in `cdk.json`. The
workflow's `cdk deploy` is a no-op for the alias (already attached by the
script) and just syncs CFN state.

## Cleanup runbook (when stable for ~1 week)

- Delete CloudFront distribution `E37G1C2KWNAPBD` (disable, wait, delete).
- Delete CloudFront Functions `apiref-phpstan-org-viewer-request` and `secure-headers-response` (the latter has no remaining users after `E37G1C2KWNAPBD` is gone).
- Delete the legacy ACM cert `arn:aws:acm:us-east-1:928192134594:certificate/18f4edec-8bec-4f52-a02b-a9738053b817` once unreferenced.
- Empty and delete S3 bucket `web-apiref.phpstan.org`.
- Delete the `APIREF_AWS_ACCESS_KEY_ID` and `APIREF_AWS_SECRET_ACCESS_KEY` GitHub secrets.
- (Optional follow-up) Migrate `update-playground-api.yml` and `update-playground-runner.yml` to OIDC — they're the last workflows in this repo still using `PLAYGROUND_RUNNER_AWS_*` static keys.
