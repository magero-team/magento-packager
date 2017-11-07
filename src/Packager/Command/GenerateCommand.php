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

use Symfony\Component\Console;

/**
 * Class GenerateCommand
 *
 * @package Magero\Packager\Command
 */
class GenerateCommand extends AbstractCommand
{
    CONST ARGUMENT_DIRECTORY = 'directory';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Generate package properties file');
        $this->addArgument(
            self::ARGUMENT_DIRECTORY,
            Console\Input\InputArgument::OPTIONAL,
            'Directory package properties file will be generated where',
            getcwd()
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $directory = $input->getArgument(self::ARGUMENT_DIRECTORY);
        if (!is_dir($directory)) {
            throw new Console\Exception\InvalidArgumentException('Invalid target directory');
        }
        $directory = realpath($directory);
        $propertiesFile = $directory . DIRECTORY_SEPARATOR . 'properties.yml';

        $propertiesStub = <<<END
name: Module_Module
version: 0.0.1
stability: stable
license: "License"
channel: community
summary: "Module short description"
description: "Module long description"
notes: "Releases notes"
authors:
    - { name: "Author Name", user: authoruser, email: user@example.com }
php_min_version: 5.4.0
php_max_version: 5.6.100
required_packages:
    - { name: Package_Name, channel: channel, min: ~, max: ~ }
END;
        file_put_contents($propertiesFile, $propertiesStub);

        $output->writeln('Package properties file created successfully: ' . $propertiesFile);
    }
}
