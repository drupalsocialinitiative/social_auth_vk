SOCIAL AUTH EXAMPLE MODULE

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * How it works

INTRODUCTION
------------

Social Auth Vkontakte Module is a VK.com Authentication integration for Drupal. It
is based on the Social Auth and Social API projects.

It adds to the site:
* A new url: /user/login/vk.
* A settings form on /admin/config/social-api/social-auth/vk page.
* A Vkontakte Logo in the Social Auth Login block.

REQUIREMENTS
------------

This module requires the following modules:

 * Social Auth (https://drupal.org/project/social_auth)
 * Social API (https://drupal.org/project/social_api)

INSTALLATION
------------

 * Install https://packagist.org/packages/vladkens/vk library via composer:
 composer require vladkens/vk

 * Install the dependencies: Social API and Social Auth.

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

 * A more comprehensive installation instruction for Drupal 8 can be found at
   https://www.drupal.org/node/2764227.

CONFIGURATION
-------------

 * Add your Vkontakte application OAuth information in
   Configuration » User Authentication » Vkontakte.
   You need to create your Vkontakte application for a website:
   https://vk.com/editapp?act=create

   Copy and insert Application ID, Private Key, Access Key 
   from your application settings to Social Auth VK configuration form:
   /admin/config/social-api/social-auth/vk

   Also you need to add all working site domains to application settings. And add 
   Verified redirect URI for each domain:
   http://example.com/user/login/vk/callback

 * Place a Social Auth Login block in Structure » Block Layout.

 * If you already have a Social Auth Login block in the site, rebuild the cache.


HOW IT WORKS
------------

Users can click on the Vkontakte logo on the Social Auth Login block
You can also add a button or link anywhere on the site that points 
to /user/login/vk, so theming and customizing the button or link
is very flexible.

When the user opens the /user/login/vk link, it automatically takes
the user to Vkontakte Accounts for authentication. Vkontakte then returns the user to
Drupal site. If we have an existing Drupal user with the same email address
provided by Vkontakte, that user is logged in. Otherwise a new Drupal user is
created.

