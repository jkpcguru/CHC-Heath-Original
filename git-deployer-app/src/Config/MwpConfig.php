<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Config;

/***
 * No path should have trailing slashes,
 * Do not use any path variable directly from here, Use MwpPathHelper because we are mocking some paths for test
 */
final class MwpConfig
{
    // Root directory of deployer user, its also a directory where github action uploads tar file
    public const DEPLOYER_ROOT_DIR = '/git-deployer';

    // Root directory of wordpress installation
    public const WP_ROOT_DIR = '/html';

    // manifest holds files list which created/ updated using git-deployer
    public const MANIFEST_FILE_NAME = 'manifest.json';

    // File under uploaded tar which has list of deleted files
    public const DELETED_FILES_NAME = 'deleted_files.txt';

    // Ignore list of files and dirs from sync, usually these are core wordpress files
    public const WP_IGNORE_FILE = [
        "deleted_files.txt",
        "index.php",
        "plat-cron.php",
        "wp-activate.php",
        "wp-blog-header.php",
        "wp-comments-post.php",
        "wp-cron.php",
        "wp-links-opml.php",
        "wp-load.php",
        "wp-login.php",
        "wp-mail.php",
        "wp-settings.php",
        "wp-signup.php",
        "wp-trackback.php",
        "xmlrpc.php",
        "platform/",
        "wp-admin/",
        "wp-includes/",
        "wp-content/mu-plugins/",
        "wp-content/object-cache.php",
        "wp-content/uploads/",
        "git-deployer-app/",
    ];
}
