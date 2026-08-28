const { execFileSync } = require( 'node:child_process' );
const { test, expect } = require( '@playwright/test' );

function wpCli( ...args ) {
	return execFileSync(
		process.platform === 'win32' ? 'npx.cmd' : 'npx',
		[ 'wp-env', 'run', 'cli', 'wp', ...args ],
		{ encoding: 'utf8' }
	).trim();
}

function createdId( output ) {
	const match = output.match( /^(\d+)$/m );

	if ( ! match ) {
		throw new Error( `Could not find the created object ID in: ${ output }` );
	}

	return Number( match[ 1 ] );
}

test( 'searches for and retains a selected category', async ( { page } ) => {
	test.setTimeout( 90_000 );
	const suffix = Date.now();
	const parentName = `DynaCat Family ${ suffix }`;
	const childName = `DynaCat Functional Child ${ suffix }`;
	const otherName = `Unrelated Topic ${ suffix }`;

	wpCli( 'plugin', 'activate', 'dynacat' );
	const parentId = createdId(
		wpCli( 'term', 'create', 'category', parentName, '--porcelain' )
	);
	const childId = createdId(
		wpCli(
			'term',
			'create',
			'category',
			childName,
			`--parent=${ parentId }`,
			'--porcelain'
		)
	);
	wpCli( 'term', 'create', 'category', otherName, '--porcelain' );
	const postId = createdId(
		wpCli(
			'post',
			'create',
			'--post_type=post',
			'--post_status=draft',
			`--post_title=DynaCat functional test ${ suffix }`,
			'--porcelain'
		)
	);

	await page.goto( '/wp-login.php' );
	await page.getByLabel( 'Username or Email Address' ).fill( 'admin' );
	await page.getByLabel( 'Password', { exact: true } ).fill( 'password' );
	await page.getByRole( 'button', { name: 'Log In' } ).click();
	await page.goto( `/wp-admin/post.php?post=${ postId }&action=edit` );

	const search = page.locator( '#categorydiv #filbox' );
	await expect( search ).toBeVisible();
	await search.fill( 'Functional Child' );

	const matchingCategory = page.locator( '#categorydiv .catlink', {
		hasText: childName,
	} );
	await expect( matchingCategory ).toBeVisible();
	await expect( page.locator( '#categorydiv .catlink', { hasText: otherName } ) ).toHaveCount( 0 );
	await matchingCategory.click();
	await expect( page.locator( '#categorydiv #post_category' ) ).toHaveValue(
		String( childId )
	);

	await page.getByRole( 'button', { name: 'Save draft' } ).click();
	await expect( page.getByText( 'Draft saved.', { exact: true } ) ).toBeVisible();
	await page.reload();

	await expect( page.locator( '#dynacat-current-category' ) ).toContainText(
		childName
	);
	await expect( page.locator( '#categorydiv #post_category' ) ).toHaveValue(
		String( childId )
	);
	const assignedCategories = wpCli(
		'post',
		'term',
		'list',
		String( postId ),
		'category',
		'--field=term_id'
	);
	expect( assignedCategories ).toMatch( new RegExp( `^${ childId }$`, 'm' ) );
} );
