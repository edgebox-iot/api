#  Edgebox API Module

An API built with PHP, running on a LAMP stack environment built using Docker Compose. It consists of the following:

* PHP
* Apache
* MySQL
* phpMyAdmin

As of now, several PHP version can be setup. Use appropriate php version as needed:

* 5.4.x
* 5.6.x
* 7.1.x
* 7.2.x
* 7.3.x
* 7.4.x
* 8.0.x WIP waiting for dependencies to introduce php8 support

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
> You have to rebuild the docker image by running `docker-compose build` and restart the docker containers.

#### Connect via SSH

You can connect to web server using `docker-compose exec` command to perform various operation on it. Use below command to login to container via ssh.

```shell
docker-compose exec webserver bash
```

## PHP

The installed version of depends on your `.env`file. 

#### Extensions

By default following extensions are installed. 
May differ for PHP Verions <7.x.x

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

> If you want to install more extension, just update `./bin/webserver/Dockerfile`. You can also generate a PR and it will be merged if it seems good for general purpose.
> You have to rebuild the docker image by running `docker-compose build` and restart the docker containers.

## phpMyAdmin

phpMyAdmin is configured to run on port 8080. Use following default credentials.

http://localhost:8080/  
username: root  
password: tiger

If running on the context of edgebox proxy service, it is available at http://pma.edgebox instead.

## Not ready for production

* secure mysql users with proper source IP limitations
