# Engage Web WordPress Plugin Instructions

This repository contains the WordPress.org plugin with slug: dynacat.

## General rules

- Treat GitHub as the source of truth.
- Never publish directly to WordPress.org unless explicitly instructed.
- Prepare changes in a branch or pull request for review.
- Do not change the WordPress.org plugin slug.
- Preserve backwards compatibility unless a breaking change is explicitly requested.
- Do not rename the plugin without explicit approval.

## Testing

Before preparing a release:

1. Test against the latest stable version of WordPress.
2. Run PHP syntax checks on all PHP files.
3. Run WordPress Plugin Check where available.
4. Confirm the plugin activates without PHP errors or warnings.
5. Confirm the WordPress admin remains accessible.
6. Test the plugin's core functionality.
7. Check for deprecated WordPress functions and obvious compatibility issues.
8. Report anything that cannot be tested automatically.

For DynaCat specifically:

- Confirm the category selector appears when editing a post.
- Confirm typing into the category search returns matching categories.
- Confirm a category can be selected.
- Confirm saving the post retains the selected category.

## WordPress.org metadata

Before preparing a release:

- Ensure the plugin header Version matches the readme.txt Stable tag.
- Only update Tested up to after testing successfully against that WordPress version.
- Update the changelog for every release.
- Check that readme.txt passes WordPress.org formatting requirements.

## Release process

- Do not create a Git tag automatically unless explicitly instructed.
- Do not deploy to WordPress.org automatically unless explicitly instructed.
- Prepare all required release changes and present them for review.
- Once approved, the existing GitHub Actions workflow will handle deployment to WordPress.org.

## Coding standards

- Follow WordPress coding and security best practices.
- New Engage Web specific functions should use the ew_ prefix.
- New WordPress plugins created by Engage Web should use Engage Web as the author.
