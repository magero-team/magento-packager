<?php
/**
 *  This file is part of the Magero Packager.
 *
 *  (c) Magero team <support@magero.pw>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Magero\Packager\Command;

use ArrayObject;
use Phar;
use PharData;
use Symfony\Component\Console;
use Symfony\Component\Finder;
use Magero\Packager\Config;

/**
 * Class PackCommand
 *
 * @package Magero\Packager\Command
 */
class PackCommand extends AbstractCommand
{
    CONST ARGUMENT_DIRECTORY = 'directory';
    CONST OPTION_PACKAGE_FILE = 'file';

    /** @var array */
    private $targetMap = [
        'magelocal' => 'app/code/local',
        'magecommunity' => 'app/code/community',
        'magecore' => 'app/code/core',
        'magedesign' => 'app/design',
        'mageetc' => 'app/etc',
        'magelib' => 'lib',
        'magelocale' => 'app/locale',
        'magemedia' => 'media',
        'mageskin' => 'skin',
        'magetest' => 'tests',
        'mage' => '',
    ];

    /** @var array */
    private $excludedFiles = [
        'package.yml',
        'packager.phar',
        'packager',
    ];

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Create Magento package from directory');
        $this->addArgument(
            self::ARGUMENT_DIRECTORY,
            Console\Input\InputArgument::REQUIRED,
            'Directory package will be created from'
        );

        $this->addOption(
            self::OPTION_PACKAGE_FILE,
            'f',
            Console\Input\InputOption::VALUE_REQUIRED,
            'Package file name or path'
        );
    }

    /**
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $sourceDirectory = $input->getArgument(self::ARGUMENT_DIRECTORY);
        if (!is_dir($sourceDirectory)) {
            $sourceDirectory = getcwd() . DIRECTORY_SEPARATOR . $sourceDirectory;
        }
        if (!is_dir($sourceDirectory)) {
            throw new Console\Exception\InvalidArgumentException('Invalid source directory: ' . $sourceDirectory);
        }
        $sourceDirectory = realpath($sourceDirectory);
        $configFile = $sourceDirectory . DIRECTORY_SEPARATOR . 'package.yml';
        if (!file_exists($configFile)) {
            throw new Console\Exception\InvalidArgumentException('Not found package.yml file: ' . $configFile);
        }
        if (!is_readable($configFile)) {
            throw new Console\Exception\InvalidArgumentException('File package.yml is unreadable: ' . $configFile);
        }

        $packageXml = <<<END
<?xml version="1.0"?>
<package>
    <name />
    <version />
    <stability />
    <license />
    <channel />
    <extends />
    <summary />
    <description />
    <notes />
    <authors />
    <date />
    <time />
    <contents />
    <compatible />
    <dependencies />
</package>
END;
        $packageXml = simplexml_load_string($packageXml);

        $config = Config::createFromFile($configFile);
        $config->configurePackage($packageXml);

        $fileName = $sourceDirectory . '/../archive.tar';
        $package = new PharData($fileName);

        foreach ($this->targetMap as $code => $relativeDirectoryPath) {
            $realDirectoryPath = $sourceDirectory . DIRECTORY_SEPARATOR . $relativeDirectoryPath;
            if (!is_dir($realDirectoryPath)) {
                continue;
            }
            $fileStructure = $this->createDirectory($realDirectoryPath);
            $finder = new Finder\Finder();
            if ($code !== 'mage') {
                $files = $finder->files()->in($realDirectoryPath);
            } else {
                $existingDirectories = $this->targetMap;
                unset($existingDirectories['mage']);
                $files = $finder->files()
                    ->in($realDirectoryPath)
                    ->exclude(array_values($existingDirectories))
                    ->filter(function($file) {
                        /** @var Finder\SplFileInfo $file */
                        return !in_array($file->getRelativePathname(), $this->excludedFiles);
                    });
            }

            if ($files->count() > 0) {
                /** @var Finder\SplFileInfo $file */
                foreach ($files as $file) {
                    $package->addFile($file->getPathname(), $relativeDirectoryPath . DIRECTORY_SEPARATOR . $file->getRelativePathname());
                    $this->addToFileStructure($file->getRelativePathname(), $fileStructure);
                }
                $config->configurePackageContents($packageXml, $code, $fileStructure);
            }
        }

        $package->addFromString('package.xml', $packageXml->asXML());
        $package = $package->compress(Phar::GZ);

        unlink($fileName);
        if ($fileName = $input->getOption(self::OPTION_PACKAGE_FILE)) {
            if (basename($fileName) == $fileName) {
                $fileName = dirname($sourceDirectory) . DIRECTORY_SEPARATOR . $fileName;
            }
        } else {
            $fileName = str_replace(
                basename($package->getPath()),
                ($config->getPackageFileName() . '.tgz'),
                $package->getPath()
            );
        }
        rename($package->getPath(), $fileName);

        $output->writeln('Package was created successfully: ' . $fileName);

        return 0;
    }

    /**
     * @param string $path
     * @param string $name
     *
     * @return ArrayObject
     */
    private function createDirectory($path = '', $name = '')
    {
        $directory = new ArrayObject();
        $directory['name'] = $name;
        $directory['real_path'] = $path;
        $directory['directories'] = [];
        $directory['files'] = [];

        return $directory;
    }

    /**
     * @param string $filePath
     * @param ArrayObject $filesStructure
     *
     * @return $this
     */
    private function addToFileStructure($filePath, $filesStructure)
    {
        $fileName = basename($filePath);

        $directories = explode(DIRECTORY_SEPARATOR, str_replace($fileName, '', $filePath));
        $directories = array_filter($directories, function($value) {
            return $value;
        });

        $directory = $filesStructure;
        $realDirPath = $filesStructure['real_path'];
        foreach ($directories as $directoryName) {
            $realDirPath .= DIRECTORY_SEPARATOR . $directoryName;
            if (!array_key_exists($directoryName, $directory['directories'])) {
                $directory['directories'][$directoryName] = $this->createDirectory($realDirPath, $directoryName);
            }
            $directory = $directory['directories'][$directoryName];
        }

        $directory['files'][$fileName] = $fileName;

        return $this;
    }
}
