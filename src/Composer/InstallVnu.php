<?php

declare(strict_types = 1);

namespace Webduck\Composer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class InstallVnu
{
    const URL = 'https://github.com/validator/validator/releases/download/18.8.29/vnu.jar_18.8.29.zip';
    const JAR_MD5_CHECKSUM = '1b7f746df9bff7df96d401b6b41fc83f';

    public static function execute()
    {
        $targetDir = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bin', 'vnu']);

        $fs = new Filesystem();

        $jarPath = $targetDir.DIRECTORY_SEPARATOR.'vnu.jar';

        if ($fs->exists($jarPath)) {
            if (md5(file_get_contents($jarPath)) == self::JAR_MD5_CHECKSUM) {
                echo "Nu Validator already installed in the right version, skipping...\n";

                return;
            } else {
                echo "Nu Validator installed but its MD5 checksum does not match. Removing and downloading fresh version...\n";
                $fs->remove($targetDir);
            }
        }

        $archiveName = 'vnu.zip';

        $process = new Process(sprintf(
            'cd %1$s && wget -O %2$s %3$s && unzip %2$s && rm %2$s && mv dist vnu',
            escapeshellarg(dirname($targetDir)),
            escapeshellarg($archiveName),
            escapeshellarg(self::URL)
        ));

        echo "Downloading Nu Validator.\n";
        echo sprintf("Running process %s\n", $process->getCommandLine());

        if ($process->run()) {
            throw new \RuntimeException(sprintf(
                "Failed to download NU Validator, process exited with code %d.\n\nOutput:\n%s\n\nError output:\n%s\n",
                $process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput()
            ));
        }

    }
}
