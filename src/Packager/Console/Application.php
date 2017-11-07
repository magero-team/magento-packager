<?php
/**
 *  This file is part of the Magero Packager.
 *
 *  (c) Magero team <support@magero.pw>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Magero\Packager\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Magero\Packager\Command;

/**
 * Class Application
 * @package Magero\Packager\Console
 */
class Application extends BaseApplication
{
    const VERSION = '1.0.0';

    /**
     * Application constructor
     */
    public function __construct()
    {
        parent::__construct('Magero Packager', self::VERSION);
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        $commands = array_merge(
            parent::getDefaultCommands(),
            array(
                new Command\GenerateCommand(),
                new Command\PackCommand(),
            )
        );

        return $commands;
    }
}
