<?php

/**
 * This file is part of the Superdesk Web Publisher Updater Bundle.
 *
 * Copyright 2015 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2015 Sourcefabric z.ú.
 * @license http://www.superdesk.org/license
 */
namespace SWP\UpdaterBundle\Manager;

use Symfony\Component\Console\Input\ArrayInput;
use Updater\Console\Application;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Updater wrapper.
 */
class Updater
{
    const UPDATE_COMMAND = 'update';

    /**
     * Runs update command with given parameters.
     *
     * @param array $parameters Command parameters
     *
     * @return string|int Command output
     */
    public static function runUpdateCommand(array $parameters = array())
    {
        $parameters = array('command' => self::UPDATE_COMMAND) + $parameters;

        return self::runCommand($parameters);
    }

    private static function runCommand(array $parameters = array())
    {
        $input = new ArrayInput($parameters);
        $output = new BufferedOutput();
        $app = new Application();
        $app->setAutoExit(false);

        $result = $app->run($input, $output);
        if ($result !== 0) {
            return $output->fetch();
        }

        return $result;
    }
}
