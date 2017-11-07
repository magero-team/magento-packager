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

use Symfony\Component\Console\Command\Command;
use Magero\Packager\Console\Application;

/**
 * Class AbstractCommand
 * @package Magero\Packager\Command
 *
 * @method Application getApplication()
 */
abstract class AbstractCommand extends Command
{
    /**
     * BaseCommand constructor
     */
    public function __construct()
    {
        $name = get_class($this);
        $name = strtolower(str_replace('Command', '', substr($name, strrpos($name, '\\') + 1)));

        parent::__construct($name);
    }
}
