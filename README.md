# Pingdom - Artandor

## Description of the project
A pingdom retrieving infos on requested websites and displaying it on a front. I use it to monitor websites i manage as a freelance.

## How to install
> composer install
> php bin/console d:m:m

## How to run
Use any webserver with php. For the database, i am using sqlite in developpement, just change the conf in order to use mysql.

> symfony server:start

## How to use
Add websites with this command
> php bin/console app:website:add --name "Once Upon A Day" --domain "https://once-upon-a-day.com/"

Remove a website with this command
> php bin/console app:website:remove --name "Once Upon A Day"

Ping all websites manually
> php bin/console app:website:ping --all

Or copy the line in crontab.txt to the crontab of your choice. You can open crontab with
> crontab -e
