<?php

namespace WakeOnWeb\BehatContexts;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelDictionary;

/**
 * @author Alexandre Tomatis <a.tomatis@wakeonweb.com>
 */
class FileContext implements Context
{
    use KernelDictionary;

    const FILE_PATTERN = '/[\w\d-]*\.[\w]*$/';
    const PATH_PATTERN = '/^[\w\d-\/]*\//';

    /** @var string */
    protected $basePath;

    /** @var array */
    protected $fileCleanQueue;

    /**
     * @param string|null $basePath
     */
    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? 'tests/functional/fixtures/file';
        $this->fileCleanQueue = [];
    }

    /**
     * @param string $path
     * @param string $file
     *
     * @Given I create file in :path from :file
     */
    public function iCreateFileFrom(string $path, string $file)
    {
        preg_match(self::FILE_PATTERN, $file, $fileName);

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $newFile = sprintf('%s/%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $path, $fileName[0]);
        copy($this->getFile($file), $newFile);
        $fileCleanQueue[] = $newFile;
    }

    /**
     * @param string $file
     * @param string $fileExpected
     *
     * @throws \Exception
     *
     * @Then The file :file must be a copy of :fileExpected
     */
    public function fileMustBeACopyOf(string $file, string $fileExpected)
    {
        preg_match(self::FILE_PATTERN, $file, $fileName);
        preg_match(self::PATH_PATTERN, $file, $path);
        $this->fileExist($fileName[0], $path[0] ?? '/');
        $file = sprintf('%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $file);

        if (file_get_contents($file) !== file_get_contents($this->getFile($fileExpected))) {
            throw new \Exception(sprintf('File %s is not identical to %s', $file, $fileExpected));
        }
    }

    /**
     * @param string $path
     * @param string $fileExpected
     *
     * @throws \Exception
     *
     * @Then One of file present in :path must be a copy of :fileExpected
     */
    public function oneOfFileMustBeACopyOf(string $path, string $fileExpected)
    {
        $folder = sprintf('%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $path);
        $found = 0;

        if (!file_exists($folder)) {
            throw new \Exception(sprintf('Folder "%s" not found.', $folder));
        }

        $files = glob(sprintf('%s/*', $folder));

        foreach ($files as $file) {
            if (file_get_contents($file) === file_get_contents($this->getFile($fileExpected))) {
                $found++;
            }
        }

        if (0 === $found) {
            throw new \Exception(sprintf('A copy of file %s not found in %s', $fileExpected, $path));
        }

        if (1 < $found) {
            throw new \Exception(sprintf('More than one file identical to %s found in %s', $fileExpected, $path));
        }
    }

    /**
     * @param string $file
     * @param int    $size
     *
     * @throws \Exception
     *
     * @Then The file :file size is less or equal to :size octet
     */
    public function fileSizeIsLessOrEqualTo(string $file, int $size)
    {
        preg_match(self::FILE_PATTERN, $file, $fileName);
        preg_match(self::PATH_PATTERN, $file, $path);
        $this->fileExist($fileName[0], $path[0] ?? '/');

        if ($size <= filesize($file)) {
            throw new \Exception(sprintf('File size is %d octet too big.', filesize($file) - $size));
        }
    }

    /**
     * @param string $file
     * @param int    $size
     *
     * @throws \Exception
     *
     * @Then The file :file size is equal to :size octet
     */
    public function fileSizeIsEqualTo(string $file, int $size)
    {
        preg_match(self::FILE_PATTERN, $file, $fileName);
        preg_match(self::PATH_PATTERN, $file, $path);
        $this->fileExist($fileName[0], $path[0] ?? '/');

        if ($size === filesize($file)) {
            throw new \Exception(sprintf('File size expected: %d, given : %d', $size, filesize($file)));
        }
    }

    /**
     * @param string $file
     * @param string $mimeType
     *
     * @throws \Exception
     *
     * @Then The file :file mime type must be equal to :mimeType
     */
    public function mimeTypeMustBeEqualTo(string $file, string $mimeType) {
        preg_match(self::FILE_PATTERN, $file, $fileName);
        preg_match(self::PATH_PATTERN, $file, $path);
        $this->fileExist($fileName[0], $path[0] ?? '/');

        if ($mimeType !== mime_content_type($file)) {
            throw new \Exception(sprintf('File mime type expected: %s, given : %s', $mimeType, mime_content_type($file)));
        }
    }

    /**
     * @param string    $file
     * @param TableNode $mimeTypes
     *
     * @throws \Exception
     *
     * @Then The file :file mime type must be equal to one of following:
     */
    public function mimeTypeMustBeEqualToOneOfFollowing(string $file, TableNode $mimeTypes)
    {
        preg_match(self::FILE_PATTERN, $file, $fileName);
        preg_match(self::PATH_PATTERN, $file, $path);
        $this->fileExist($fileName[0], $path[0] ?? '/');

        if (!in_array(mime_content_type($file), $mimeTypes->getColumn(0))) {
            throw new \Exception(sprintf('File mime type expected: %s, given : %s', implode(', ', $mimeTypes->getColumn(0)), mime_content_type($file)));
        }
    }

    /**
     * @param string $folder
     *
     * @throws \Exception
     *
     * @Then I clean all files from folder :folder
     */
    public function iCleanAllFilesFromFolder(string $folder) {
        $folder = sprintf('%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $folder);

        if (!file_exists($folder)) {
            throw new \Exception(sprintf('Folder "%s" not found.', $folder));
        }

        $files = glob(sprintf('%s/*', $folder));
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * @param string $path
     * @param int    $number
     *
     * @throws \Exception
     *
     * @Then :number files must be present in folder :path
     */
    public function fileNumberMustBeEqualTo(string $path, int $number)
    {
        $folder = sprintf('%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $path);

        if (!file_exists($folder)) {
            throw new \Exception(sprintf('Folder "%s" not found.', $folder));
        }

        $fileNumber = count(glob(sprintf('%s/*', $path)));

        if($fileNumber !== $number) {
            throw new \Exception(sprintf('%d files found in "%s". %d expected.', $fileNumber, $path, $number));
        }
    }

    /**
     * @param string $fileName
     * @param string $path
     *
     * @throws \Exception
     *
     * @Then The file with name :fileName must be present in :path
     */
    public function fileExist(string $fileName, string $path)
    {
        $file = sprintf('%s/%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $path, $fileName);

        if (!file_exists($path)) {
            throw new \Exception(sprintf('Folder "%s" not found.', $path));
        }

        if (!file_exists($file)) {
            $filesFound = glob(preg_replace(self::FILE_PATTERN, '*', $file));
            $filesFound = 0 === count($filesFound) ? '' : PHP_EOL.PHP_EOL.'Files found :'.PHP_EOL.'  - '.implode(PHP_EOL.'  - ', $filesFound);

            throw new \Exception(sprintf('File "%s" don\'t exist.%s', $file, $filesFound));
        }
    }

    /**
     * @BeforeScenario @clean-files-before
     * @AfterScenario @clean-files-after
     *
     * @Then I clean generated files
     */
    public function iCleanGeneratedFiles()
    {
        foreach ($this->fileCleanQueue as $file) {
            unlink($file);
        }
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getFile(string $fileName): string
    {
        return sprintf('%s/%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $this->basePath, $fileName);
    }
}
