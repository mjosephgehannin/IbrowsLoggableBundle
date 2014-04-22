IbrowsLoggableBundle
=============================




Install & setup the bundle
--------------------------

1. Add IbrowsLoggableBundle in your composer.json:

	```js
	{
	    "require": {
	        "ibrows/loggable-bundle": "~1.0",
	    }
	}
	```

2. Now tell composer to download the bundle by running the command:

    ``` bash
    $ php composer.phar update ibrows/loggable-bundle
    ```

    Composer will install the bundle to your project's `ibrows/loggable-bundle` directory. ( PSR-4 )

3. Add the bundles to your `AppKernel` class

    ``` php
    // app/AppKernerl.php
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new Ibrows\LoggableBundle\IbrowsLoggableBundle(),
            // ...
        );
        // ...
    }
    ```

