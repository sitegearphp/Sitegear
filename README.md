Sitegear
========

Sitegear is an extensible, configurable and modular website management application.

Sitegear consists of a website framework and a set of integrated content management tools.  It is aimed primarily at
websites based on owner-driven driven content.  One of the primary driving goals is to reduce development time for
these types of websites specifically.  For more dynamic, user-driven websites, please consider an MVC framework.

Modules
-------

Sitegear is modular, allowing unlimited possibilities for extensibility.  There are several core modules, which are
always available.  Higher level functionality is found in extension modules.

Each module may serve one or more specific purposes:

 * Container for entities
 * Provider of controller methods and related view scripts
 * Provider of client-side resources (JavaScript, CSS, etc)
 * Provider of user management tools

Customisation
-------------

Sitegear is highly configurable, directly via a config file mechanism.  This means that much of the application's
behaviour can be modified by changing some (well-documented!) settings.  Configuration can also be applied on a
per-environment basis.  This is useful, for example, to test and demonstrate a mail form without spamming the "real"
target email address, while knowing that in production, the correct email address will be used.

View scripts provided by modules can also be very easily overridden, by providing a site-specific view script with the
relevant filename (and path).  This allows for further customisation as the entire markup can be modified.

DSL
---

Sitegear features a simple yet powerful Domain Specific Language for view scripts.  This provides access to a range of
functionality using a structured call pattern:

    <?php echo $view->foo()->bar(); ?>

Where `foo` is either the name of a module, or one of several special "pseudo-module" specifiers, and `bar` is the name
of a target within the specified module.

Foundation Technologies
-----------------------

The Sitegear application is based on:

 * PHP 5.3
 * Symfony Components
 * Doctrine
 * PSR-3 compatible logging interfaces

The content management tools operate in the web browser, and are based on:

 * jQuery and jQueryUI
 * (some wysiwyg editor, unsure which one yet)
 * (some file browser, unsure which one yet)

Unit Tests
----------

Unit test coverage is in progress, but all tests should pass.  To run the tests:

    composer install --dev
    phpunit

Further Information
-------------------

For more information, see the website: http://sitegear.org/

Available under MIT open source license.  See the LICENSE for details.
