<?php
/**
 * Update DynaCat release metadata after compatibility tests pass.
 *
 * Usage: php scripts/prepare-release.php <plugin-version> <wordpress-version>
 *
 * @package DynaCat
 */

if ( 3 !== $argc ) {
	fwrite( STDERR, "Usage: php scripts/prepare-release.php <plugin-version> <wordpress-version>\n" );
	exit( 1 );
}

$release_version = $argv[1];
$tested_up_to    = $argv[2];

if ( ! preg_match( '/^\d+\.\d+(?:\.\d+)?$/', $release_version ) ) {
	fwrite( STDERR, "Invalid plugin version: {$release_version}\n" );
	exit( 1 );
}

if ( ! preg_match( '/^\d+\.\d+$/', $tested_up_to ) ) {
	fwrite( STDERR, "Invalid WordPress version: {$tested_up_to}\n" );
	exit( 1 );
}

$plugin_file = dirname( __DIR__ ) . '/dynacat-plugin.php';
$readme_file = dirname( __DIR__ ) . '/readme.txt';
$plugin      = file_get_contents( $plugin_file );
$readme      = file_get_contents( $readme_file );

if ( false === $plugin || false === $readme ) {
	fwrite( STDERR, "Could not read the plugin metadata files.\n" );
	exit( 1 );
}

if ( ! preg_match( '/^ \* Version: (\S+)$/m', $plugin, $matches ) ) {
	fwrite( STDERR, "Could not find the current plugin version.\n" );
	exit( 1 );
}

if ( version_compare( $release_version, $matches[1], '<=' ) ) {
	fwrite( STDERR, "Release version must be newer than {$matches[1]}.\n" );
	exit( 1 );
}

$plugin = preg_replace( '/^ \* Version: \S+$/m', " * Version: {$release_version}", $plugin, 1, $plugin_count );
$readme = preg_replace( '/^Tested up to: .*$/m', "Tested up to: {$tested_up_to}", $readme, 1, $tested_count );
$readme = preg_replace( '/^Stable tag: .*$/m', "Stable tag: {$release_version}", $readme, 1, $stable_count );

$changelog = "= {$release_version} =\n* Confirm compatibility with WordPress {$tested_up_to}.\n\n";
$readme    = preg_replace( '/^(== Changelog ==\R\R)/m', '$1' . $changelog, $readme, 1, $changelog_count );

if ( 1 !== $plugin_count || 1 !== $tested_count || 1 !== $stable_count || 1 !== $changelog_count ) {
	fwrite( STDERR, "Could not update all release metadata.\n" );
	exit( 1 );
}

if ( false === file_put_contents( $plugin_file, $plugin ) || false === file_put_contents( $readme_file, $readme ) ) {
	fwrite( STDERR, "Could not write the plugin metadata files.\n" );
	exit( 1 );
}

printf( "Prepared DynaCat %s, tested up to WordPress %s.\n", $release_version, $tested_up_to );
