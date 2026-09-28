# Deploying DynaCat to WordPress.org

GitHub is the source of truth for DynaCat. The manually triggered maintenance
workflow is a release workflow: it tests the plugin against the latest stable
WordPress, updates the release metadata, creates a Git commit and tag, builds an
installable ZIP, and deploys the same package to WordPress.org.

## One-time repository setup

1. Confirm that the WordPress.org account has commit access to the `dynacat`
   plugin repository.
2. In the GitHub repository, open **Settings → Secrets and variables → Actions**
   and add these repository secrets:
   - `SVN_USERNAME`: the WordPress.org username with commit access.
   - `SVN_PASSWORD`: the account's WordPress.org SVN password. For an account
     with two-factor authentication enabled, generate the SVN password from the
     account's WordPress.org profile. Do not use a WordPress site application
     password or a GitHub token.
3. Keep the plugin slug as `dynacat`. The workflow publishes to that
   WordPress.org SVN repository.
4. Ensure GitHub Actions has permission to write repository contents and that
   the `main` branch rules allow this workflow to push the generated release
   commit and tag.

Use a dedicated WordPress.org release account where possible. Never add either
credential to the workflow file or commit it to Git. These must be Actions
repository or organisation secrets; secrets saved only for Codespaces,
Dependabot, or a GitHub environment are not available to this reusable workflow.
The caller explicitly maps both secret names to the shared release workflow so a
missing or renamed credential cannot be obscured by blanket secret inheritance.

Changing a secret's value does not require a code change or a new pull request.
GitHub never shows the stored value again, so re-enter both secrets rather than
assuming that an existing entry is current. If organisation secrets are used,
also confirm that their repository access policy includes `dynacat`; a secret
with the right name but without access is unavailable to this workflow.

## Prepare a release

1. Make any plugin changes in a pull request, obtain approval, and merge them to
   `main`.
2. Choose the next plugin version. It must be greater than the current `Version`
   and must not already exist as a Git or WordPress.org SVN tag.
3. Do not edit `Version`, `Stable tag`, `Tested up to`, or the changelog manually;
   the workflow updates them only after the compatibility tests pass.

## Validate and deploy

1. Open **Actions → Engage Web Plugin Maintenance → Run workflow**.
2. Select `main` in **Use workflow from**.
3. Enter the new plugin version in **New version**.
4. Select **Test, version, tag, and deploy this release to WordPress.org**.
5. Select **Run workflow** and review every job result.

The workflow first reads the current version directly from the plugin header and
shows it in the run summary next to the requested release version. GitHub does
not support dynamically populating a `workflow_dispatch` text box from a
repository file, so the input deliberately has no hard-coded default. To check
the current version without releasing, leave the version blank and leave the
confirmation option disabled; the current-version job will still produce the
summary while the release job remains skipped.

The shared workflow installs the test dependencies, starts the latest stable WordPress,
runs the functional tests, and reads that installation's WordPress version. Once
the tests pass, it sets `Version` and `Stable tag` to the requested plugin
version, sets `Tested up to` to the tested WordPress major/minor version, and adds
a changelog entry.

The updated package then passes WordPress Plugin Check. The workflow creates
`dynacat.zip` and uploads it as an artifact retained for 14 days, commits the
metadata to `main`, creates the matching Git tag, and publishes `trunk` plus the
new version tag to the WordPress.org SVN repository. The deployment checks out
`trunk` directly, commits its synchronized contents, and then creates the tag
from the committed remote trunk. This avoids depending on a sparse local
checkout when creating the release tag. The package and deployment steps reject
any `node_modules` directory. During deployment, files removed from the clean
package are also scheduled for deletion in Subversion. A legacy `node_modules`
tree is scheduled for deletion before package synchronization, while it still
exists in the working copy, so Subversion can remove the directory as one tree
instead of failing while processing thousands of individually missing paths.
Any failure stops later steps, so a failed compatibility test cannot update
metadata or deploy.

### Recover from an SVN authentication failure

The messages `E215004: Authentication failed` and `E215004: No more
credentials or we tried too many times` come from WordPress.org's SVN server.
They mean that the deployment reached WordPress.org but it rejected the supplied
credentials; rerunning the same values cannot repair the problem.

1. Confirm that `SVN_USERNAME` is the WordPress.org **username**, not the
   account's email address or display name.
2. Confirm on the plugin's WordPress.org **Advanced View** that this username is
   listed as a committer.
3. Generate or reset the account's WordPress.org SVN password, then re-enter
   `SVN_USERNAME` and `SVN_PASSWORD` in the GitHub Actions repository secrets.
4. If either value comes from an organisation secret, confirm that `dynacat` is
   an allowed repository. A repository secret with the same name takes
   precedence, so remove or update any stale repository-level copy.
5. Do not start another release merely to test the credentials. Ask a maintainer
   of the shared `Engage-Web/github-actions` release workflow to resume the
   failed WordPress.org deployment from the already-built artifact.

The last point is important because the shared workflow creates the release
commit and Git tag before its SVN deployment step. After a late authentication
failure, the plugin header and Git history may already contain the requested
version even though WordPress.org does not. The normal release entry point
intentionally rejects that same version as "not newer"; incrementing the
version would hide the incomplete deployment rather than fix it. Verify the
GitHub run's **Commit and tag checked release** step and WordPress.org before
choosing a recovery path.

The workflow only runs its release job from `main`, and the confirmation checkbox
must be selected. Its current-version preview does not publish anything.

When confirmation is selected, the caller checks the requested version before it
starts the shared release job. The version must have two or three numeric
components and must be greater than the version currently in the plugin header.
For example, after `1.40` has been released, rerun with `1.41` (or a later
version), not `1.40`. This early check also prevents the shared workflow's
always-run WordPress cleanup from producing a secondary `wp-env: not found`
message when release validation stops the job before Node dependencies are
installed. In older runs that show both errors, expand **Validate release** to
find the primary failure; **Stop WordPress** is only a cleanup failure caused by
the earlier validation failure.

The release implementation remains in the central
`Engage-Web/github-actions` repository. DynaCat supplies only its slug, plugin
file, display name, requested version, and test profile. Other Engage Web plugin
repositories continue to call the same reusable workflow with their own values,
so release fixes and checks can be maintained once for all participating plugins.

## Verify the release

After a successful workflow run:

1. Review the workflow log for the WordPress.org SVN revision and tag.
2. Check the public DynaCat page and download package on WordPress.org after its
   caches update.
3. Install the published package on a clean WordPress site and confirm that it
   activates without warnings.
4. Edit a post and confirm that the category selector appears, category search
   returns matches, a category can be selected, and the selection remains after
   saving.

Do not retry deployment from a different commit using the same version tag. If
the published artifact needs a code change, prepare and review a new release.
