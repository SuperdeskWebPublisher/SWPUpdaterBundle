# UpdaterBundle
[![Build Status](https://travis-ci.org/SuperdeskWebPublisher/SWPUpdaterBundle.svg?branch=master)](https://travis-ci.org/SuperdeskWebPublisher/SWPUpdaterBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SuperdeskWebPublisher/SWPUpdaterBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/SuperdeskWebPublisher/SWPUpdaterBundle/?branch=master)
[![Code Climate](https://codeclimate.com/github/SuperdeskWebPublisher/SWPUpdaterBundle/badges/gpa.svg)](https://codeclimate.com/github/SuperdeskWebPublisher/SWPUpdaterBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/75867a30-2f5e-4b00-85d9-dd10a743042f/mini.png)](https://insight.sensiolabs.com/projects/75867a30-2f5e-4b00-85d9-dd10a743042f)

Provides integration for [updater](https://github.com/ahilles107/updater) which gives an easy way to update your application.

## Installation

Installation is a 8 step process:

1. Download SWPUpdaterBundle
2. Enable the bundle and its dependencies
3. Create your own `Version` class or use existing one
4. Import SWPUpdaterBundle routing file
5. Configure the FOSRestBundle
6. Configure the SensioFrameworkExtraBundle
7. Configure the Symfony FrameworkBundle
8. Configure the SWPUpdaterBundle

### Step 1: Install SWPUpdaterBundle with Composer

Run the following composer require command:

``` bash
$ php composer.phar require swp/updater-bundle

```

### Step 2: Enable the bundle and its dependencies

Enable the bundle in `AppKernel.php` and its all dependencies (FOSRestBundle, JMSSerializerBundle, NelmioApiDocBundle)


``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new SWP\UpdaterBundle\SWPUpdaterBundle(),
        new FOS\RestBundle\FOSRestBundle(),
        new JMS\SerializerBundle\JMSSerializerBundle(),
        new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
    );
}
```

### Step 3: Create your own `Version` class or use existing one

This bundle requires your own `Version` class to read the current application's version and apply available updates based on that. 
You must create your own `Version` class for your application which must implement `SWP\UdaterBundle\Version\VersionInterface` interface which is provided by this bundle. See the example below.

```php
<?php

namespace Acme\DemoBundle\Version;

use SWP\UpdaterBundle\Version\VersionInterface;

final class Version implements VersionInterface
{
    private $version = '0.0.1';

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }
}
```

### Step 4: Import SWPUpdaterBundle routing file

You have to import SWPUpdaterBundle routing file. You can use YAML or XML format.

YAML:

``` yaml
# app/config/routing.yml
swp_updater:
    resource: "@SWPUpdaterBundle/Resources/config/routing.yml"
    prefix:   /
```

XML:

``` xml
<!-- app/config/routing.xml -->
<import resource="@SWPUpdaterBundle/Resources/config/routing.xml" prefix="/" />
```

### Step 5: Configure the FOSRestBundle

FOSRestBundle will be installed automatically for you. You just have to configure it.
SWPUpdaterBundle provides an API endpoints to get and post appropiate data.
To make use of the API provided by SWPUpdaterBundle you have to configure FOSRestBundle properly.

FOSRestBundle provides various tools to rapidly develop RESTful API's with Symfony2.
Below example provides ready to be used YAML configuration.

Thanks to NelmioApiDocBundle, the SWPUpdaterBundle API documentation can be available under the url: http://example.com/api/doc

For more informations about the configuration of the FOSRestBundle, please see [documentation](http://symfony.com/doc/master/bundles/FOSRestBundle/index.html)

```yaml
# app/config/config.yml
fos_rest:
    routing_loader:
        default_format: json
    view:
        formats:
            json: true
        view_response_listener: 'force'
    format_listener:
        rules:
            - { path: '^/api', priorities: ['json'], fallback_format: json, prefer_extension: true }
            - { path: '^/', stop: true }
    exception:
        codes:
            'Symfony\Component\Routing\Exception\ResourceNotFoundException': 404
        messages:
            'Symfony\Component\Routing\Exception\ResourceNotFoundException': true
```

### Step 6: Configure the SensioFrameworkExtraBundle

Symfony FrameworkBundle will be installed automatically for you. You just have to configure it.

```yaml
# app/config/config.yml
sensio_framework_extra:
    view:    { annotations: false }
```

### Step 7: Configure the Symfony FrameworkBundle

Symfony FrameworkBundle will be installed automatically for you. You just have to configure it.

```yaml
# app/config/config.yml
framework:
	templating:
    	engines: ['twig']
```

### Step 8: Configure the SWPUpdaterBundle

Now that you have your own `Version` class, it's time to configure the bundle for your needs.

Add the following parameter to your `parameters.yml` file.

```yaml
# app/config/parameters.yml
parameters:
	swp_updater.version.class: "Acme\DemoBundle\Version\Version"
```

Add the following configuration to your `config.yml` file.

```yaml
# app/config/config.yml
swp_updater:
	version_class: %swp_updater.version.class%
	client:
	    base_uri: http://example.com
```

At this stage, the bundle is ready to be used by your application.

#### Adding custom http client options:

SWPUpdaterBundle uses Guzzle to fetch data from the external server which provides informations about the update packages. You can add custom Guzzle options / headers for your http client by simply adding an array of options as a parameter. The example below shows how to add custom curl options.

```yaml
# app/config/parameters.yml
parameters:
    swp_updater.client.options:
        curl: # http://guzzle.readthedocs.org/en/latest/faq.html#how-can-i-add-custom-curl-options
            10203: # integer value of CURLOPT_RESOLVE
                - "example.com:localhost" # This will resolve the host example.com to your localhost
```

For more details see [Guzzle documentation](http://guzzle.readthedocs.org/en/latest/request-options.html).

#### Changing default directories:

There are two types of directories used by the Http client:
 - temporary directory - specifies where the update packages will be downlaoded and extacted, defaults to `app/cache/<env>` where `<env>`  can be `dev`, `test` or `prod`.
 - target directory - directory which should be updated, defaults to the current application directory.

Those directories can be changed by setting `temp_dir` and `target_dir` options in your config.yml file.

 ```yaml
# app/config/config.yml
swp_updater:
	temp_dir: "/some/temp/dir"
	target_dir: "/some/target/dir"
```


#### Enabling Monolog channel for SWPUpdaterBundle:

It is possible to enable a separate Monolog channel to which all logs will be forwarded. You will have then a separate log file for the SWPUpdaterBundle which will be saved under the directory `app/logs/` and will be named `updater_<env>-<current_date>.log`.
By default, separate channel is disabled. You can enable it by setting `monolog_channel` option to `true` and configuring Monolog.

```yaml
# app/config/config.yml
swp_updater:
	monolog_channel: true

monolog:
    channels: ["updater"]
    handlers:
        updater:
            type:  rotating_file # creates a new file every day (http://symfony.com/doc/current/cookbook/logging/monolog.html#how-to-rotate-your-log-files)
            path:  %kernel.logs_dir%/updater_%kernel.environment%.log
            level: debug
            max_files: 10
            channels: ["updater"]

```

For more details see the [Monolog documentation](http://symfony.com/doc/current/cookbook/logging/channels_handlers.html).
