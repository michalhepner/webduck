<?php

declare(strict_types = 1);

namespace Webduck\Domain\Audit;

use RuntimeException;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\Insight;

class HtmlAudit implements AuditInterface
{
    const NAME = 'Html';
    const TYPE_MAP = [
        'info' => Insight::MARK_WARNING,
        'error' => Insight::MARK_ERROR,
        'non-document-error' => Insight::MARK_ERROR,
    ];

    /**
     * @var string
     */
    protected $vnuJar;

    /**
     * @var SplFileInfo
     */
    protected $tmpDir;

    /**
     * @var string
     */
    protected $javaBin;

    /**
     * @param string|SplFileInfo $vnuJar
     * @param string|SplFileInfo $tmpDir
     * @param string|null        $javaBin
     */
    public function __construct($vnuJar, $tmpDir, ?string $javaBin = null)
    {
        if (is_string($vnuJar)) {
            $vnuJar = new SplFileInfo($vnuJar);
        } elseif (!$vnuJar instanceof SplFileInfo) {
            throw new \InvalidArgumentException(sprintf(
                'Argument 1 passed to %s must be a string or instance of SplFileInfo, %s provided',
                __METHOD__,
                gettype($vnuJar)
            ));
        }

        if (is_string($tmpDir)) {
            $tmpDir = new SplFileInfo($tmpDir);
        } elseif (!$tmpDir instanceof SplFileInfo) {
            throw new \InvalidArgumentException(sprintf(
                'Argument 2 passed to %s must be a string or instance of SplFileInfo, %s provided',
                __METHOD__,
                gettype($tmpDir)
            ));
        }

        $this->vnuJar = $vnuJar;
        $this->tmpDir = $tmpDir;
        $this->javaBin = !empty($javaBin) ? $javaBin : 'java';
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function execute(Browse $urlData): InsightCollection
    {
        $results = new InsightCollection();

        if (empty(trim($urlData->getHtml()))) {
            return $results;
        }

        $this->ensureTmpDirExists();

        $htmlFile = new SplFileInfo($this->tmpDir->getPathname().DIRECTORY_SEPARATOR.bin2hex(openssl_random_pseudo_bytes(20)));
        $fs = new Filesystem();

        $fs->dumpFile($htmlFile->getPathname(), $urlData->getHtml());

        $process = new Process(sprintf(
            'cd %s && %s -jar %s --exit-zero-always --format json %s',
            escapeshellarg(dirname($this->vnuJar->getPathname())),
            escapeshellarg($this->javaBin),
            escapeshellarg($this->vnuJar->getFilename()),
            escapeshellarg($htmlFile->getPathname())
        ));

        $process->run();

        $fs->remove($htmlFile->getPathname());

        if ($process->getExitCode()) {
            throw new RuntimeException(sprintf(
                "Process `%s` exited with code %d.\n\nOutput:\n%s\n\nError output:\n%s\n",
                $process->getCommandLine(),
                $process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput()
            ));
        }

        $errors = json_decode($process->getErrorOutput(), true);

        if (is_array($errors) && array_key_exists('messages', $errors)) {
            foreach ($errors['messages'] as $error) {
                $data = $error;
                foreach (['type', 'url', 'message'] as $keyToUnset) {
                    if (array_key_exists($keyToUnset, $data)) {
                        unset($data[$keyToUnset]);
                    }
                }

                $results->add(new Insight(self::NAME, self::TYPE_MAP[$error['type']], $error['message'], $data));
            }
        }

        return $results;
    }

    public function unserialize($serialized)
    {
    }

    public function serialize()
    {
        return serialize(null);
    }

    protected function ensureTmpDirExists()
    {
        if (!$this->tmpDir->isDir()) {
            (new Filesystem())->mkdir($this->tmpDir->getPathname());
        }
    }
}
