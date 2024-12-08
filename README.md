#  Edgebox API Module

[![CI](https://github.com/edgebox-iot/api/actions/workflows/ci.yml/badge.svg)](https://github.com/edgebox-iot/api/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/edgebox-iot/edgeboxctl/branch/main/graph/badge.svg?token=G7I9QC5CN7)](https://codecov.io/gh/edgebox-iot/edgeboxctl)

The API and web-interface module built with PHP, running on a LAMP stack environment built using Docker Compose or Edgebox Compose. It consists of the following:

* PHP
* Apache
* MySQL
* phpMyAdmin (not available when using [ws](https://github.com/edgebox-iot/ws))

PHP version is:

* 8.1

It can be upgraded by editing the Dockerfile in the bin folder (please open a PR).


##  Installation
 
* Clone this repository on your local computer, on your edgebox working folder (eg: ~/edgebox/)
* configure .env as needed 
* Run the `docker-compose up -d`.

```shell
git clone https://github.com/edgebox-iot/api.git
cd api/
cp sample.env .env
// modify sample.env as needed
docker-compose up -d
// visit localhost
```

Your API LAMP stack is now ready!! You can access it via `http://localhost`.

To run it on the context of edgebox-iot/ws:
* Clone this repository on your local computer, on your edgebox working folder (eg: ~/edgebox/)
* Navigate to the ws folder (eg: ~/edgebox/ws) 
* Run `./ws -b`.
* Run `./ws -s`

```shell
cd ~/edgebox
git clone https://githib.com/edgebox-iot/ws.git
git clone https://github.com/edgebox-iot/api.git
cd ws/
./ws -b
./ws -s
// visit api.edgebox
```

Your API LAMP stack is now ready and running behind Edgebox proxy service!! You can access it via `http://api.edgebox`.

##  Configuration and Usage

### Configuration
This package comes with default configuration options. You can modify them by creating `.env` file in your root directory.
To make it easy, just copy the content from `sample.env` file and update the environment variable values as per your need.

### Configuration Variables
There are following configuration variables available and you can customize them by overwritting in your own `.env` file.

---
#### PHP
---
_**PHPVERSION**_
Is used to specify which PHP Version you want to use. Defaults always to latest PHP Version. 

_**PHP_INI**_
Define your custom `php.ini` modification to meet your requirments. 

---
#### Apache 
---

_**DOCUMENT_ROOT**_

It is a document root for Apache server. The default value for this is `./www`. All your sites will go here and will be synced automatically.

_**VHOSTS_DIR**_

This is for virtual hosts. The default value for this is `./config/vhosts`. You can place your virtual hosts conf files here.

> Make sure you add an entry to your system's `hosts` file for each virtual host.

_**APACHE_LOG_DIR**_

This will be used to store Apache logs. The default value for this is `./logs/apache2`.

---
#### Database
---

_**DATABASE**_
Define which MySQL or MariaDB Version you would like to use. 

_**MYSQL_DATA_DIR**_

This is MySQL data directory. The default value for this is `./data/mysql`. All your MySQL data files will be stored here.

_**MYSQL_LOG_DIR**_

This will be used to store Apache logs. The default value for this is `./logs/mysql`.

## Web Server

Apache is configured to run on port 80. So, you can access it via `http://localhost`.

#### Apache Modules

By default following modules are enabled.

* rewrite
* headers

> If you want to enable more modules, just update `./bin/webserver/Dockerfile`. You can also generate a PR and we will merge if seems good for general purpose.
> You[![CI](https://github.com/edgebox-iot/api/actions/workflows/ci.yml/badge.svg)](https://github.com/edgebox-iot/api/actions/workflows/ci.yml) have to rebuild the docker image by running `docker-compose build` and restart the docker containers.

#### Connect via SSH

You can connect to web server using `docker-compose exec` command to perform various operation on it. Use below command to login to container via ssh.

```shell
docker-compose exec webserver bash
```

### PHP Extensions

By default following extensions are installed. 

* mysqli
* pdo_sqlite
* pdo_mysql
* mbstring
* zip
* intl
* mcrypt
* curl
* json
* iconv
* xml
* xmlrpc
* gd

> If you want to install more extensions, just update `./Dockerfile`. You can also generate a PR and it will be merged if it seems good for general purpose.
> You have to rebuild the docker image by running `docker-compose build` and restart the docker containers.

## phpMyAdmin

phpMyAdmin is configured to run on port 8080. Use following default credentials.

http://localhost:8080/  
username: root  
password: tiger

If running on the context of edgebox proxy service, it is available at http://pma.edgebox instead.

## Interface

The projects includes a web interface that is meant to be used either in the local network or via the myedge.app tunnel.

It is a early version and **lots of the functionality is not yet working**.

The interface is built using bootstrap / saas, using html5 and css best practices. It is based on [soft-ui-dashboard by CreativeTim](https://github.com/creativetimofficial/soft-ui-dashboard) and uses twig templating. Some screenshots:

![Dashboard Home](https://user-images.githubusercontent.com/1270431/115163576-1f99f500-a0aa-11eb-85be-0169f71b568c.png)

![EdgeApps Screen](https://user-images.githubusercontent.com/1270431/115163589-2e80a780-a0aa-11eb-88f9-87d0b34e6290.png)

![SettingsScreen](https://user-images.githubusercontent.com/1270431/115163599-393b3c80-a0aa-11eb-8e86-aa21307f5c86.png)

![ComingSoonScreen](https://user-images.githubusercontent.com/1270431/115163605-4526fe80-a0aa-11eb-98af-4529aaeab94e.png)

![ActionScreen](https://user-images.githubusercontent.com/1270431/115163614-4fe19380-a0aa-11eb-992e-2a361cd8ffce.png)


