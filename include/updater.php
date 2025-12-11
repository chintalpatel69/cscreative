<?php
/**
 * include/updater.php
 * Simple GitHub release-based theme updater (no external libraries)
 *
 * Requirements:
 * - Public GitHub repo (works with public repos without token)
 * - Theme folder slug: cscreative (adjust if different)
 *
 * How it works:
 * - Calls GitHub Releases API (cached as transient) to get latest release
 * - Compares release tag / version to style.css Version:
 * - If newer, injects update into WP using 'site_transient_update_themes'
 * - When WP downloads & unpacks the zip, upgrader_post_install moves files into correct theme dir
 *
 * Optional: define GITHUB_UPDATES_TOKEN constant in wp-config.php or here to avoid rate-limits.
 */

// ---------- CONFIG ----------
if ( ! defined( 'CSGIT_GITHUB_USER' ) ) {
    define( 'CSGIT_GITHUB_USER', 'chintalpatel69' ); // <--- REPLACE
}
if ( ! defined( 'CSGIT_GITHUB_REPO' ) ) {
    define( 'CSGIT_GITHUB_REPO', 'cscreative' ); // <--- REPLACE (likely "cscreative")
}
if ( ! defined( 'CSGIT_THEME_SLUG' ) ) {
    define( 'CSGIT_THEME_SLUG', 'cscreative' ); // folder name / theme slug
}
// How long to cache GitHub release metadata (in seconds)
if ( ! defined( 'CSGIT_CACHE_TTL' ) ) {
    define( 'CSGIT_CACHE_TTL', 6 * HOUR_IN_SECONDS ); // 6 hours
}
// Optional: set a GitHub personal access token (string) to avoid rate limits.
// Do NOT hardcode tokens in distributed themes. You may set this in wp-config.php.
if ( ! defined( 'CSGIT_GITHUB_TOKEN' ) ) {
    // define( 'CSGIT_GITHUB_TOKEN', 'ghp_xxx' );
    // leave empty by default
    define( 'CSGIT_GITHUB_TOKEN', '' );
}

// ---------- Implementation ----------
add_filter( 'site_transient_update_themes', 'csgit_check_for_theme_update' );
add_filter( 'transient_update_themes', 'csgit_check_for_theme_update' ); // compatibility
add_filter( 'upgrader_post_install', 'csgit_upgrader_post_install', 10, 3 );

/**
 * Check and inject theme update into WP transient
 *
 * @param object $transient
 * @return object
 */
function csgit_check_for_theme_update( $transient ) {
    // If no transient object passed, return.
    if ( ! is_object( $transient ) ) {
        return $transient;
    }

    $theme_slug = CSGIT_THEME_SLUG;
    $theme = wp_get_theme( $theme_slug );

    // If theme not installed or invalid, bail.
    if ( ! $theme || ! $theme->exists() ) {
        return $transient;
    }

    $current_version = $theme->get( 'Version' );
    if ( empty( $current_version ) ) {
        return $transient;
    }

    // Get release info from GitHub (cached)
    $release = csgit_get_github_latest_release();
    if ( ! $release || empty( $release['tag_name'] ) ) {
        return $transient;
    }

    // Normalize version strings (strip leading 'v' if present)
    $remote_version_raw = $release['tag_name'];
    $remote_version = ltrim( $remote_version_raw, 'vV' );

    // If remote version is not greater than current version, nothing to do
    if ( version_compare( $remote_version, $current_version, '<=' ) ) {
        return $transient;
    }

    // Build download package link - prefer zipball_url (works for public repos)
    $package = ! empty( $release['zipball_url'] ) ? $release['zipball_url'] : '';

    // If you attached a release asset named cscreative.zip in Releases, you can set package to that asset browser_download_url
    if ( empty( $package ) && ! empty( $release['assets'] ) ) {
        // pick first asset with .zip extension
        foreach ( $release['assets'] as $asset ) {
            if ( ! empty( $asset['browser_download_url'] ) && preg_match( '/\.zip$/i', $asset['name'] ) ) {
                $package = $asset['browser_download_url'];
                break;
            }
        }
    }

    if ( empty( $package ) ) {
        // no package to install
        return $transient;
    }

    // Prepare theme update response object
    $update = new stdClass();
    $update->id = 0;
    $update->slug = $theme_slug;
    $update->new_version = $remote_version;
    $update->url = ! empty( $release['html_url'] ) ? $release['html_url'] : "https://github.com/" . CSGIT_GITHUB_USER . "/" . CSGIT_GITHUB_REPO;
    $update->package = $package;
    // optional metadata
    $update->tested = '';    // you can set tested WP version if known
    $update->requires = '';  // minimum WP required, set if desired

    // Inject into transient
    $transient->response[ $theme_slug ] = $update;

    return $transient;
}

/**
 * Retrieve GitHub latest release metadata (cached in transient)
 *
 * @return array|false
 */
function csgit_get_github_latest_release() {
    $cache_key = 'csgit_github_release_' . CSGIT_GITHUB_USER . '_' . CSGIT_GITHUB_REPO;
    $cached = get_transient( $cache_key );
    if ( $cached !== false ) {
        return $cached;
    }

    $api_url = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', rawurlencode( CSGIT_GITHUB_USER ), rawurlencode( CSGIT_GITHUB_REPO ) );

    $args = array(
        'timeout' => 15,
        'headers' => array(
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WP-GitHub-Theme-Updater/' . get_bloginfo( 'url' ),
        ),
    );

    // optional token header
    if ( ! empty( CSGIT_GITHUB_TOKEN ) ) {
        $args['headers']['Authorization'] = 'token ' . CSGIT_GITHUB_TOKEN;
    }

    $response = wp_remote_get( $api_url, $args );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( ! is_array( $data ) ) {
        return false;
    }

    // Cache it
    set_transient( $cache_key, $data, CSGIT_CACHE_TTL );

    return $data;
}

/**
 * After WP unpacks the zip, ensure installed folder is moved into the correct theme directory.
 * Handles the case where GitHub zip contains an extra parent folder name.
 *
 * @param mixed $result
 * @param array $hook_extra
 * @param WP_Upgrader $upgrader
 * @return mixed
 */
function csgit_upgrader_post_install( $result, $hook_extra, $upgrader ) {
    // Only act for themes
    if ( empty( $hook_extra['type'] ) || 'theme' !== $hook_extra['type'] ) {
        return $result;
    }

    // If a specific theme is being updated, check slug
    if ( ! empty( $hook_extra['theme'] ) && $hook_extra['theme'] !== CSGIT_THEME_SLUG ) {
        return $result;
    }

    // $result contains destination and source paths in different forms depending on upgrader
    // $upgrader->skin, $upgrader->result etc. We'll try to move the newly unpacked folder into theme directory.

    // Determine WP filesystem and ensure correct permissions
    if ( ! function_exists( 'get_filesystem_method' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $method = get_filesystem_method();
    // Use WP_Filesystem to move directories properly
    global $wp_filesystem;
    if ( empty( $wp_filesystem ) ) {
        // initialize
        $creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );
        if ( ! WP_Filesystem( $creds ) ) {
            // could not init filesystem, return original result
            return $result;
        }
    }

    // Path where WP unzipped the package (varies)
    $source = isset( $upgrader->skin->result['destination'] ) ? $upgrader->skin->result['destination'] : '';
    if ( empty( $source ) && isset( $result['destination'] ) ) {
        $source = $result['destination'];
    }

    // If still no source, try to detect from $upgrader->result
    if ( empty( $source ) && ! empty( $upgrader->result ) && is_array( $upgrader->result ) ) {
        // try to find a path that is inside WP_CONTENT_DIR
        foreach ( $upgrader->result as $maybe ) {
            if ( is_string( $maybe ) && strpos( $maybe, WP_CONTENT_DIR ) !== false ) {
                $source = $maybe;
                break;
            }
        }
    }

    // Target theme dir
    $theme_dir = get_theme_root() . '/' . CSGIT_THEME_SLUG;

    // If source is a folder that contains the theme files but wrapped in a parent dir (github zip), attempt to detect real folder
    if ( ! empty( $source ) && is_dir( $source ) ) {
        // if the source folder already matches the target path, nothing to do
        if ( realpath( $source ) === realpath( $theme_dir ) ) {
            return $result;
        }

        // If the source contains a single subfolder (common with GitHub zip), use that
        $children = scandir( $source );
        $candidates = array();
        foreach ( $children as $c ) {
            if ( $c === '.' || $c === '..' ) continue;
            $full = $source . '/' . $c;
            if ( is_dir( $full ) ) $candidates[] = $full;
        }

        // If there's exactly one candidate directory inside, assume it's the real theme dir inside zip (github zipball)
        if ( count( $candidates ) === 1 ) {
            $real_source = $candidates[0];
        } else {
            // otherwise use source as-is
            $real_source = $source;
        }

        // Remove existing theme dir backup (if WP moved it to .old or similar, allow replacement)
        if ( is_dir( $theme_dir ) && ! is_link( $theme_dir ) ) {
            // try to remove old theme dir (WP already renames old theme files, but just in case)
            // we attempt to copy/replace files rather than remove entire dir to be safe
            // We'll use WP_Filesystem to remove the target dir first
            $wp_filesystem->rmdir( $theme_dir, true );
        }

        // Now move/copy real_source to target theme_dir
        $copied = copy_dir( $real_source, $theme_dir );

        // If copy_dir failed, try fallback: rename
        if ( ! $copied ) {
            // attempt rename (may fail due to perms)
            @rename( $real_source, $theme_dir );
        }

        // Clean up: remove the temporary unpacked directory if it's not the same as theme_dir
        if ( is_dir( $source ) && realpath( $source ) !== realpath( $theme_dir ) ) {
            // attempt recursive remove
            csgit_recursive_rmdir( $source );
        }
    }

    return $result;
}

/**
 * Copy directory helper (uses PHP functions - WP_Filesystem may be used instead)
 */
function copy_dir( $source, $dest ) {
    if ( ! is_dir( $source ) ) return false;

    if ( ! is_dir( $dest ) ) {
        if ( ! mkdir( $dest, 0755, true ) ) return false;
    }

    $items = scandir( $source );
    foreach ( $items as $item ) {
        if ( $item === '.' || $item === '..' ) continue;
        $src = trailingslashit( $source ) . $item;
        $dst = trailingslashit( $dest ) . $item;
        if ( is_dir( $src ) ) {
            if ( ! copy_dir( $src, $dst ) ) return false;
        } else {
            if ( ! copy( $src, $dst ) ) return false;
        }
    }
    return true;
}

/**
 * Recursive remove directory
 */
function csgit_recursive_rmdir( $dir ) {
    if ( ! is_dir( $dir ) ) return;
    $items = scandir( $dir );
    foreach ( $items as $item ) {
        if ( $item === '.' || $item === '..' ) continue;
        $path = $dir . '/' . $item;
        if ( is_dir( $path ) ) {
            csgit_recursive_rmdir( $path );
        } else {
            @unlink( $path );
        }
    }
    @rmdir( $dir );
}
