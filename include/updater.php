<?php
/**
 * include/updater.php
 * GitHub release-based theme updater (no external libraries)
 *
 * Requirements:
 * - Public GitHub repo (works with public repos without token)
 * - Theme folder slug: cscreative (adjust if different)
 */

// ---------- CONFIG ----------
if ( ! defined( 'CSGIT_GITHUB_USER' ) ) {
    define( 'CSGIT_GITHUB_USER', 'chintalpatel69' );
}
if ( ! defined( 'CSGIT_GITHUB_REPO' ) ) {
    define( 'CSGIT_GITHUB_REPO', 'cscreative' );
}
if ( ! defined( 'CSGIT_THEME_SLUG' ) ) {
    define( 'CSGIT_THEME_SLUG', 'cscreative' );
}
if ( ! defined( 'CSGIT_CACHE_TTL' ) ) {
    define( 'CSGIT_CACHE_TTL', 6 * HOUR_IN_SECONDS );
}
if ( ! defined( 'CSGIT_GITHUB_TOKEN' ) ) {
    define( 'CSGIT_GITHUB_TOKEN', '' );
}

// ---------- Implementation ----------
add_filter( 'site_transient_update_themes', 'csgit_check_for_theme_update' );
add_filter( 'transient_update_themes', 'csgit_check_for_theme_update' ); // compatibility
add_filter( 'upgrader_post_install', 'csgit_upgrader_post_install', 10, 3 );

/**
 * Check and inject theme update into WP transient
 *
 * @param object|false $transient
 * @return object|false
 */
function csgit_check_for_theme_update( $transient ) {
    if ( ! is_object( $transient ) ) {
        return $transient;
    }

    $theme_slug = CSGIT_THEME_SLUG;
    $theme = wp_get_theme( $theme_slug );

    if ( ! $theme || ! $theme->exists() ) {
        return $transient;
    }

    $current_version = $theme->get( 'Version' );
    if ( empty( $current_version ) ) {
        return $transient;
    }

    $release = csgit_get_github_latest_release();
    if ( ! $release || empty( $release['tag_name'] ) ) {
        return $transient;
    }

    // Normalize version strings
    $remote_version_raw = $release['tag_name'];
    $remote_version = ltrim( $remote_version_raw, 'vV' );

    if ( version_compare( $remote_version, $current_version, '<=' ) ) {
        return $transient;
    }

    // Determine package URL (zipball or asset)
    $package = ! empty( $release['zipball_url'] ) ? $release['zipball_url'] : '';

    if ( empty( $package ) && ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
        foreach ( $release['assets'] as $asset ) {
            if ( ! empty( $asset['browser_download_url'] ) && preg_match( '/\.zip$/i', $asset['name'] ) ) {
                $package = $asset['browser_download_url'];
                break;
            }
        }
    }

    if ( empty( $package ) ) {
        return $transient;
    }

    // Build update as an associative array (WordPress expects arrays, not objects)
    $update = array(
        'id'          => 0,
        'slug'        => $theme_slug,
        'new_version' => $remote_version,
        'url'         => ! empty( $release['html_url'] ) ? $release['html_url'] : "https://github.com/" . CSGIT_GITHUB_USER . "/" . CSGIT_GITHUB_REPO,
        'package'     => $package,
        'tested'      => '',
        'requires'    => '',
    );

    // Ensure transient->response is an array
    if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
        $transient->response = array();
    }

    $transient->response[ $theme_slug ] = $update;

    return $transient;
}

/**
 * Retrieve GitHub latest release metadata (cached)
 *
 * @return array|false
 */
function csgit_get_github_latest_release() {
    $cache_key = 'csgit_github_release_' . CSGIT_GITHUB_USER . '_' . CSGIT_GITHUB_REPO;
    $cached = get_transient( $cache_key );
    if ( $cached !== false ) {
        return $cached;
    }

    $api_url = sprintf(
        'https://api.github.com/repos/%s/%s/releases/latest',
        rawurlencode( CSGIT_GITHUB_USER ),
        rawurlencode( CSGIT_GITHUB_REPO )
    );

    $site_url = get_bloginfo( 'url' );
    if ( empty( $site_url ) ) {
        $site_url = 'WordPress';
    }

    $args = array(
        'timeout' => 15,
        'headers' => array(
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WP-GitHub-Theme-Updater/' . $site_url,
        ),
    );

    if ( defined( 'CSGIT_GITHUB_TOKEN' ) && ! empty( CSGIT_GITHUB_TOKEN ) ) {
        $args['headers']['Authorization'] = 'token ' . CSGIT_GITHUB_TOKEN;
    }

    $response = wp_remote_get( $api_url, $args );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    if ( 200 !== $code ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true ); // decode as associative array

    if ( ! is_array( $data ) ) {
        return false;
    }

    set_transient( $cache_key, $data, CSGIT_CACHE_TTL );

    return $data;
}

/**
 * After WP unpacks the zip, ensure installed folder is moved into the correct theme directory.
 *
 * @param mixed $result
 * @param array $hook_extra
 * @param object $upgrader
 * @return mixed
 */
function csgit_upgrader_post_install( $result, $hook_extra, $upgrader ) {
    if ( empty( $hook_extra['type'] ) || 'theme' !== $hook_extra['type'] ) {
        return $result;
    }

    if ( ! empty( $hook_extra['theme'] ) && $hook_extra['theme'] !== CSGIT_THEME_SLUG ) {
        return $result;
    }

    // Ensure WP file functions are available
    if ( ! function_exists( 'get_filesystem_method' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    global $wp_filesystem;
    if ( empty( $wp_filesystem ) ) {
        // Try to initialize filesystem (best effort). If it fails, continue using direct PHP functions.
        $creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );
        if ( $creds && WP_Filesystem( $creds ) ) {
            global $wp_filesystem;
        } else {
            // WP_Filesystem not available - we'll fallback to PHP filesystem functions.
            $wp_filesystem = null;
        }
    }

    // Determine the unpacked source path robustly (support arrays and objects)
    $source = '';

    // 1) upgrader->skin->result could be an array or object
    if ( isset( $upgrader->skin ) && isset( $upgrader->skin->result ) ) {
        $s = $upgrader->skin->result;
        if ( is_array( $s ) && isset( $s['destination'] ) ) {
            $source = $s['destination'];
        } elseif ( is_object( $s ) && isset( $s->destination ) ) {
            $source = $s->destination;
        }
    }

    // 2) $result param may contain destination
    if ( empty( $source ) ) {
        if ( is_array( $result ) && isset( $result['destination'] ) ) {
            $source = $result['destination'];
        } elseif ( is_object( $result ) && isset( $result->destination ) ) {
            $source = $result->destination;
        }
    }

    // 3) upgrader->result may be array or object listing paths
    if ( empty( $source ) && ! empty( $upgrader->result ) ) {
        $maybe_list = $upgrader->result;
        if ( is_array( $maybe_list ) ) {
            foreach ( $maybe_list as $maybe ) {
                if ( is_string( $maybe ) && strpos( $maybe, WP_CONTENT_DIR ) !== false ) {
                    $source = $maybe;
                    break;
                }
            }
        } elseif ( is_object( $maybe_list ) ) {
            foreach ( get_object_vars( $maybe_list ) as $maybe ) {
                if ( is_string( $maybe ) && strpos( $maybe, WP_CONTENT_DIR ) !== false ) {
                    $source = $maybe;
                    break;
                }
            }
        }
    }

    $theme_dir = trailingslashit( get_theme_root() ) . untrailingslashit( CSGIT_THEME_SLUG );

    if ( ! empty( $source ) && is_dir( $source ) ) {
        // If source is already the theme directory, nothing to do
        if ( realpath( $source ) === realpath( $theme_dir ) ) {
            return $result;
        }

        // If source contains a single child dir (zip usually contains top-level folder), use it
        $children = scandir( $source );
        $candidates = array();
        foreach ( $children as $c ) {
            if ( $c === '.' || $c === '..' ) {
                continue;
            }
            $full = $source . '/' . $c;
            if ( is_dir( $full ) ) {
                $candidates[] = $full;
            }
        }

        if ( count( $candidates ) === 1 ) {
            $real_source = $candidates[0];
        } else {
            // multiple children or none -> keep source as-is
            $real_source = $source;
        }

        // Remove existing theme dir (use wp_filesystem if possible)
        if ( is_dir( $theme_dir ) && ! is_link( $theme_dir ) ) {
            if ( $wp_filesystem && method_exists( $wp_filesystem, 'rmdir' ) ) {
                $wp_filesystem->rmdir( $theme_dir, true );
            } else {
                csgit_recursive_rmdir( $theme_dir );
            }
        }

        // Copy files from real_source to theme_dir
        $copied = false;
        if ( function_exists( 'copy_dir' ) ) {
            $copied = copy_dir( $real_source, $theme_dir );
        } else {
            $copied = csgit_copy_dir( $real_source, $theme_dir );
        }

        // Fallback: try rename if copying didn't work (best-effort)
        if ( ! $copied ) {
            @clearstatcache();
            if ( ! @rename( $real_source, $theme_dir ) ) {
                // Last effort: attempt a recursive copy via WP_Filesystem if available
                if ( $wp_filesystem && method_exists( $wp_filesystem, 'copy' ) ) {
                    // attempt to create target and copy each file
                    if ( ! is_dir( $theme_dir ) ) {
                        $wp_filesystem->mkdir( $theme_dir );
                    }
                    // Not all versions of WP_Filesystem expose a copy_dir helper; fall back to PHP copy
                    $copied = csgit_copy_dir( $real_source, $theme_dir );
                }
            } else {
                $copied = true;
            }
        }

        // Remove temp source directory if different from theme dir
        if ( is_dir( $source ) && realpath( $source ) !== realpath( $theme_dir ) ) {
            csgit_recursive_rmdir( $source );
        }
    }

    return $result;
}

/**
 * Fallback copy implementation only if WP's copy_dir is not present.
 */
if ( ! function_exists( 'csgit_copy_dir' ) ) {
    function csgit_copy_dir( $source, $dest ) {
        if ( ! is_dir( $source ) ) {
            return false;
        }
        if ( ! is_dir( $dest ) ) {
            if ( ! mkdir( $dest, 0755, true ) ) {
                return false;
            }
        }
        $items = scandir( $source );
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }
            $src = trailingslashit( $source ) . $item;
            $dst = trailingslashit( $dest ) . $item;
            if ( is_dir( $src ) ) {
                if ( ! csgit_copy_dir( $src, $dst ) ) {
                    return false;
                }
            } else {
                if ( ! @copy( $src, $dst ) ) {
                    return false;
                }
            }
        }
        return true;
    }
}

/**
 * Recursive remove directory
 */
if ( ! function_exists( 'csgit_recursive_rmdir' ) ) {
    function csgit_recursive_rmdir( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $items = scandir( $dir );
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }
            $path = $dir . '/' . $item;
            if ( is_dir( $path ) ) {
                csgit_recursive_rmdir( $path );
            } else {
                @unlink( $path );
            }
        }
        @rmdir( $dir );
    }
}
