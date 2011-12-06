# Synth Memcache Bundle

A simple implementation of memcache for use with plain PHP. Not for use with Doctrine (there are
much better versions of that elsewhere!).

Initial code taken from the [Clock](http://www.clock.co.uk)
[Atrox3 Framework](https://github.com/PabloSerbo/Atrox3). Very much a work in progress! Feel free
to fork and fix as you see fit =)

## Installation

Update your `deps` file, and add the following lines:

    [SynthMemcacheBundle]
        git=http://github.com/synthmedia/SynthMemcacheBundle.git
        target=/bundles/Synth/MemcacheBundle

After that, just install the new dependencies:

    $ ./bin/vendor install

Register the bundle namespace in the autoloader (if you haven't already):

    // app/autoloader.php
    $autoloader->registerNamespaces(array(
        // ...
        'Synth'       => __DIR__.'/../vendor/bundles',
    ));

Finally, make sure that the `SynthMemcacheBundle` is registered in the application kernel:

    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new Synth\MemcacheBundle\SynthMemcacheBundle(),
        );
    }

## Basic Usage

...

## Credits

[Paul Serby](https://github.com/PabloSerbo/) of [Clock](http://www.clock.co.uk)
[Dom Udall](https://github.com/dmno/)

## Licence
Licenced under the [New BSD License](http://opensource.org/licenses/bsd-license.php)
