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
namespace SWP\UpdaterBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class SWPUpdaterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers SWP\UpdaterBundle\SWPUpdaterBundle
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::load
     * @covers SWP\UpdaterBundle\DependencyInjection\Configuration::getConfigTreeBuilder
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::<private>
     */
    public function testLoad()
    {
        $curlOptions = array(
            'curl' => array(
                '10203' => 'somehost:localhost',
            ),
        );

        $data = array(
            'swp_updater.version.class' => 'Acme\DemoBundle\Version\Version',
            'swp_updater.client.options' => $curlOptions,
        );

        $container = $this->createContainer($data);
        $loader = $this->createLoader();
        $config = $this->getConfig();

        $loader->load(array($config), $container);

        $this->assertEquals('Acme\DemoBundle\Version\Version', $container->getParameter('swp_updater.version_class'));
        $this->assertEquals(array('base_uri' => 'http://example.com'), $container->getParameter('swp_updater.client'));
        $this->assertEquals($curlOptions, $container->getParameter('swp_updater.client.options'));
        $this->assertEquals('Acme\DemoBundle\Version\Version', $container->getParameter('swp_updater.version.class'));
        $this->assertEquals($container->getParameter('kernel.cache_dir'), $container->getParameter('swp_updater.temp_dir'));
        $this->assertEquals($container->getParameter('kernel.root_dir').'/../', $container->getParameter('swp_updater.target_dir'));
        $this->assertFalse($container->hasParameter('swp_updater.monolog_channel'));
    }

    /**
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::load
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::<private>
     */
    public function testLoadWhenTempDirAndTargetDirAreSet()
    {
        $container = $this->createContainer();
        $loader = $this->createLoader();
        $directories = array(
            'version_class' => 'Acme\DemoBundle\Version\Version',
            'temp_dir' => 'some/temp/dir',
            'target_dir' => 'some/target/dir',
        );

        $config = $this->getConfig();
        $loader->load(array(array_merge($config, $directories)), $container);
        $this->assertEquals(
            $container->getParameter('kernel.root_dir').'/some/temp/dir',
            $container->getParameter('swp_updater.temp_dir')
        );

        $this->assertEquals(
            'some/target/dir',
            $container->getParameter('swp_updater.target_dir')
        );
    }

    /**
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::load
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::<private>
     */
    public function testLoadIfMonologChannelDefined()
    {
        $container = $this->createContainer();
        $loader = $this->createLoader();
        $config = $this->getConfig();
        $tempConfig = array(
            'version_class' => 'Acme\DemoBundle\Version\Version',
            'monolog_channel' => true,
        );

        $loader->load(array(array_merge($config, $tempConfig)), $container);
        $this->assertTrue($container->hasParameter('swp_updater.monolog_channel'));
        $this->assertTrue($container->getParameter('swp_updater.monolog_channel'));
    }

    /**
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::load
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testLoadWhenVersionClassIsRequired()
    {
        $container = $this->createContainer();
        $loader = $this->createLoader();

        $loader->load(array(array()), $container);
    }

    /**
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::load
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testLoadWhenClientBaseUriIsRequiredAndCannotBeEmpty()
    {
        $container = $this->createContainer();
        $loader = $this->createLoader();

        $config = array(
            'version_class' => 'Acme\DemoBundle\Version\Version',
            'client' => array(),
        );

        $loader->load(array($config), $container);
    }

    /**
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::load
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::<private>
     * @expectedException \LogicException
     */
    public function testLoadWhenClientTypeIsNotSupported()
    {
        $container = $this->createContainer();
        $loader = $this->createLoader();

        $tempConfig = array(
            'client' => array(
                'type' => 'some_fake_name',
                'base_uri' => 'http://example.com',
            ),
        );

        $config = $this->getConfig();
        $loader->load(array(array_merge($config, $tempConfig)), $container);
    }

    /**
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::load
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::<private>
     * @expectedException \LogicException
     */
    public function testLoadWhenGuzzleIsNotInstalledButUsed()
    {
        $container = $this->createContainer();
        $loader = $this->createLoader();

        $tempConfig = array(
            'client' => array(
                'type' => 'guzzle',
                'base_uri' => 'http://example.com',
            ),
        );

        $config = $this->getConfig();
        $loader->load(array(array_merge($config, $tempConfig)), $container);

        $stub = $this->getMock('SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension', array('get'));
        $stub->expects($this->at(0))
            ->method('get')
            ->will($this->throwException(new \LogicException('error')));

        $this->assertFalse($stub->get());
    }

    /**
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::load
     * @covers SWP\UpdaterBundle\DependencyInjection\SWPUpdaterExtension::<private>
     */
    public function testLoadForDefaultClient()
    {
        $container = $this->createContainer();
        $loader = $this->createLoader();
        $config = $this->getConfig();
        $tempConfig = array(
            'client' => array(
                'base_uri' => 'http://example.com',
            ),
        );

        $loader->load(array(array_merge($config, $tempConfig)), $container);
        $this->assertEquals(
            $container->getDefinition('swp_updater.client')->getClass(),
            "SWP\UpdaterBundle\Client\DefaultClient"
        );
    }

    protected function createLoader()
    {
        return new SWPUpdaterExtension();
    }

    protected function getConfig()
    {
        return array(
            'version_class' => 'Acme\DemoBundle\Version\Version',
            'client' => array(
                'base_uri' => 'http://example.com',
            ),
        );
    }

    protected function createContainer(array $data = array())
    {
        return new ContainerBuilder(new ParameterBag(array_merge(array(
            'kernel.cache_dir' => __DIR__,
            'kernel.root_dir' => __DIR__,
        ), $data)));
    }
}
