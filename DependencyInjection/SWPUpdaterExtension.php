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
namespace SWP\UpdaterBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SWPUpdaterExtension extends Extension
{
    private $container;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->container = $container;
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new Loader\YamlFileLoader($this->container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->checkAndSetSupportedClass($config);

        $clientConfig = array();
        if (!empty($config['client'])) {
            foreach (array('base_uri') as $key) {
                $clientConfig[$key] = $config['client'][$key];
            }

            $this->container->setParameter($this->getAlias().'.client', $clientConfig);
        }

        $options = array();
        if ($this->container->hasParameter($this->getAlias().'.client.options')) {
            $options = $this->container->getParameter($this->getAlias().'.client.options');
        }

        $this->container->setParameter($this->getAlias().'.client.options', $options);

        if (!empty($config['version_class'])) {
            $this->container->setParameter($this->getAlias().'.version_class', $config['version_class']);
        }

        $this->setDirectories($config);

        if (true === $config['monolog_channel']) {
            $this->container->setParameter($this->getAlias().'.monolog_channel', true);
        }
    }

    private function checkAndSetSupportedClass(array $config)
    {
        $supported = array(
            'default' => 'SWP\UpdaterBundle\Client\DefaultClient',
            'guzzle' => 'SWP\UpdaterBundle\Client\GuzzleClient',
        );

        list($class) = explode(':', $config['client']['type'], 2);
        if (!isset($supported[$class])) {
            throw new \LogicException(sprintf('Client "%s" is not supported by the UpdaterBundle.', $class));
        }

        if ($config['client']['type'] === 'guzzle' && !class_exists('GuzzleHttp\Client')) {
            throw new \LogicException('guzzlehttp/guzzle needs to be installed in order to use guzzle client!');
        }

        $this->container->getDefinition('swp_updater.client')->setClass($supported[$class]);
    }

    private function setDirectories($config)
    {
        foreach (array('temp_dir', 'target_dir') as $value) {
            if ($this->isDefault($config[$value])) {
                $this->container->setParameter(
                    $this->getAlias().'.'.$value,
                    $this->checkDirType($value)
                );
            } else {
                $this->container->setParameter(
                    $this->getAlias().'.'.$value,
                    $this->checkNotDefaultDirType($value, $config[$value])
                );
            }
        }
    }

    private function checkDirType($dir)
    {
        if ($dir === 'temp_dir') {
            return $this->container->getParameter('kernel.cache_dir');
        }

        return $this->container->getParameter('kernel.root_dir').'/../';
    }

    private function checkNotDefaultDirType($dir, $configDir)
    {
        if ($dir === 'target_dir') {
            return $configDir;
        }

        return $this->container->getParameter('kernel.root_dir').'/'.$configDir;
    }

    private function isDefault($dir)
    {
        if ($dir === 'default') {
            return true;
        }

        return false;
    }
}
