<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Helpers;

class ShellCommandHelper
{
    /**
     * @return array{output: string, exitCode: int}
     */
    private static function run(string $command): array
    {
        \exec(\escapeshellcmd($command). " 2>&1", $output, $exitCode);
        $output = \implode(PHP_EOL, $output) . PHP_EOL;
        return ['output' => $output, 'exitCode' => $exitCode];
    }

    /**
     * @param string $filename - File name or multiple filename space separated
     * @return array{output: string, exitCode: int}
     */
    public static function deleteFile(string $filename): array
    {
        return self::run("rm -rf ".$filename);
    }

    /**
     * Check if WordPress is healthy using WP-CLI.
     *
     * @param string $wpRootPath The path to the WordPress installation.
     * @return bool Returns true if WordPress is healthy, false otherwise.
     */
    public static function isWordPressHealthy(string $wpRootPath): bool
    {
        $command = "wp core is-installed --path=" . \escapeshellarg($wpRootPath);

        $output = self::run($command);
        return $output['exitCode'] === 0;
    }

    /**
     * @param string $wpRootPath - WordPress root dir
     * @return array{output: string, exitCode: int}
     */
    public static function wpCacheFlush(string $wpRootPath): array
    {
        $command = "wp wpaas cache flush --path=".\escapeshellarg($wpRootPath);
        return self::run($command);
    }
}
