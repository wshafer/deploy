<?php

namespace Reliv\Deploy\Helper;


use Psr\Log\LoggerInterface;

class FileHelper
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Create a Directory
     *
     * @param string $dir Directory Path to create
     *
     * @return void
     */
    public function createDirectory($dir)
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new \RuntimeException(
                    "Unable to create directory: ".$dir
                );
            }
        }
    }

    /**
     * Recursive remove directory.  Equivalent to `rm -Rf`
     *
     * @param string $dir Directory to remove
     *
     * @return bool
     */
    public function delTree($dir)
    {
        if (!is_dir($dir) && !is_file($dir) && !is_link($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;

            if (is_link($path) || is_file($path)) {
                unlink($path);
            } else {
                $this->delTree($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Get the deployed releases
     *
     * @param string $appBaseDir Application Base Directory
     *
     * @return array
     */
    public function getReleases($appBaseDir)
    {
        if (!is_dir($appBaseDir)) {
            return array();
        }

        $dirListing = @scandir($appBaseDir);

        $return = array();

        if ($dirListing && is_array($dirListing)) {
            natsort($dirListing);

            foreach ($dirListing as $revision) {
                $fullPath = rtrim($appBaseDir.DIRECTORY_SEPARATOR.$revision, "/\\\t\n\r\0\x0B");

                if (is_link($fullPath)
                    || strpos($revision, '.') === 0
                ) {
                    continue;
                }

                $return[$revision] = $fullPath;
            }
        }

        return $return;
    }

    /**
     * Clean the application directory
     *
     * @param string $appBaseDir         Application Base Directory
     * @param string $currentRevisionDir Current Revision Direcoty
     * @param string $numberToKeep       Number of revisions to keep
     *
     * @return void
     */
    public function cleanAppDir($appBaseDir, $currentRevisionDir, $numberToKeep)
    {
        if (empty($appBaseDir) || !is_dir($appBaseDir)) {
            throw new \InvalidArgumentException(
                "Application Base directory is invalid"
            );
        }

        if (empty($currentRevisionDir) || !is_dir($currentRevisionDir)) {
            throw new \InvalidArgumentException(
                "Current directory is invalid"
            );
        }

        if (empty($numberToKeep) || !is_numeric($numberToKeep)) {
            return;
        }

        $releases = $this->getReleases($appBaseDir);
        $foundCurrent = false;
        $counter = 0;

        while (count($releases) > 0) {
            $dir = array_pop($releases);

            if ($dir == $currentRevisionDir) {
                $counter++;
                $foundCurrent = true;
                continue;
            }

            if ($foundCurrent && $counter < $numberToKeep) {
                $counter++;
                continue;
            }

            $this->delTree($dir);
        }
    }
}
