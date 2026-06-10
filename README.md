# Pingdom - Artandor

## Description of the project
A pingdom retrieving infos on requested websites and displaying it on a front. I use it to monitor websites i manage as a freelance.

## How to install

> symfony composer install
> symfony console `d:s:u --force`

## How to run
Use any webserver with php. For the database, i am using sqlite in developpement, just change the conf in order to use mysql.

> symfony server:start

## How to use

Add and remove websites to track within the admin panel.

Ping all websites manually
> symfony console app:website:ping --all

To automatically ping websites, every 5 minutes, open a consumer in the background, it will ping all websites thanks to
@src/Command/WebsitePingCommand.php
> symfony console messenger:consume scheduler_default
