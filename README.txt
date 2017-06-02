SOCIAL AUTH VKONTAKTE MODULE

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
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
 * vladkens/vk (https://packagist.org/packages/vladkens/vk)

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

 * You need to create your Vkontakte application for a website:
   https://vk.com/editapp?act=create

   Copy and insert Application ID, Secure Key, Service token 
   from your application settings to Social Auth VK configuration form:
   /admin/config/social-api/social-auth/vk

   Also you need to add all working site domains to application settings. And add 
   Authorized redirect URI for each domain:
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

SOCIAL AUTH VKONTAKTE МОДУЛЬ

СОДЕРЖАНИЕ ФАЙЛА
---------------------

 * Описание
 * Зависимости
 * Установка
 * Настройка
 * Как это работает

ОПИСАНИЕ
------------

Social Auth Vkontakte модуль позволяет авторизироваться через VK.com. Он
основан на модулях Social Auth и Social API.

Модуль добавляет на сайт:
* Новую страниу: /user/login/vk.
* Форму настроек /admin/config/social-api/social-auth/vk.
* Логотип Vkontakte на Social Auth Login блок.

ЗАВИСИМОСТИ
------------

Этот модуль требует следующие модули и библиотеки:

 * Social Auth (https://drupal.org/project/social_auth)
 * Social API (https://drupal.org/project/social_api)
 * vladkens/vk (https://packagist.org/packages/vladkens/vk)

УСТАНОВКА
------------

 * Установите https://packagist.org/packages/vladkens/vk библиотеку через composer:
 composer require vladkens/vk

 * Установите модули: Social API и Social Auth.

 * Установите Social Auth VK как обычный модуль. Смотрите больше информации здесь:
   https://drupal.org/documentation/install/modules-themes/modules-8
  
 * Более расширенную документацию по установке Drupal модулей можете посмотреть здесь:
   https://www.drupal.org/node/2764227.

НАСТРОЙКА
-------------

 * Вам потребуется создать приложение Вконтакте для вашего сайта:
   https://vk.com/editapp?act=create

   Скопируйте и вставьте ID приложения (Application ID), Защищённый ключ (Secure Key), 
   Сервисный ключ доступа (Service token) из настроек своего приложения в конфигурационную форму:
   /admin/config/social-api/social-auth/vk

   Также вам потребуется добавить в настройки приложения все работающие домены сайта. И добавить 
   Доверенный redirect URI для каждого домена:
   http://example.com/user/login/vk/callback

 * Выведите блок Social Auth Login в Структура » Схема блоков.

 * Если вы уже вывели этот блок, то просто почистите кеш.


КАК ЭТО РАБОТАЕТ
------------

Пользователь может кликнуть на лого Вконтакте в блоке Social Auth Login.
Вы также можете разместить кнопку входа через вконтакте где угодно с ссылкой 
на страницу /user/login/vk, так что эту кнопку можно застилизовать как угодно.

Когда пользователь открывать страницу /user/login/vk, он автоматически запрашивает данные
пользователя Вконтакте для авторизации. Вконтакте возвращает пользвоателя обратно на сайт. 
Если пользователь уже существует на сайте с таким же емайл как пользвоатель Вконтакте, то
мы авторизуем пользователя. Если нет то будет создан новый пользователь.

