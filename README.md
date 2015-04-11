# KML Plugin for [Lithium PHP](http://lithify.me)

A plugin to assist with the creation of KML Files from Database Models.

> Currently supports [Google KML](http://maps.google.com/)

## Installation

__Composer__ (best option)

Modify your projects `composer.json` file

~~~ json
{
    "require": {
    	...
        "johnny13/li3_backpack": "dev-master"
        ...
    }
}
~~~

Run `php composer.phar install` (or `php composer.phar update`) and, aside from adding it to your Libraries, you should be good to go.

***

__Submodule__ (If you feel like it)

From the root of your app run `git submodule add git://github.com/johnny13/li3_backpack.git libraries/li3_analytics`

***

__Clone Directly__ (meh)

From your apps `libraries` directory run `git clone git://github.com/johnny13/li3_backpack.git`

## Usage

### Load the plugin

Add the plugin to be loaded with Lithium's autoload magic

In `app/config/bootstrap/libraries.php` add:

~~~ php
<?php
	Libraries::add('li3_analytics');
?>
~~~

### Create KML Files

There is a repo with an example app. johnny13/backpack.

## Contribute
Have an idea for a tracker? Wanna take point on one of the trackers listed above? __Please do!__

Fork and submit a pull request, lets make this awesome.

## Credits

TODO