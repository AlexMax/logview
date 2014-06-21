logview
=======

A _fast_ way to read and search logfiles on a machine, right from a browser.

Before You Begin...
-------------------

It is important to understand the security implications of serving logfiles with potentially sensitive information to an outside source.  I have done my best to prevent the application from nosing around on your system where it isn't supposed to, but know that the contents of your logfiles could potentially be used against you.

Requirements
------------

- PHP 5.4 or later.
  - Support for 5.3 is a future (unlikely) possibility.
- HTTP server such as Apache or nginx.
- Shell access to the machine you want to install this application on.

Installation
------------

Eventually, this project will downloadable from packagist.  Until then:

1. Extract the application somehow (either through `git clone` or the __Download ZIP__ button on GitHub) to its own directory and switch to it.
2. If you don't have Composer installed, go ahead and [install it now](https://getcomposer.org/download/).
3. Run `./composer install` to install all dependencies.
4. Configure your webserver to point to the `webroot` directory, configuring it so if a file is not found, it then runs `webroot/index.php` as a PHP script.
5. Configure logview itself.

Configuring Apache
------------------

TODO

Configuring nginx
-----------------

Here is a sample configuration block for nginx.

    server {
        server_name logview.example.com;

        access_log /var/log/nginx/logview.access.log;
        error_log /var/log/nginx/logview.error.log;

        # Location of logview webroot directory
        root /srv/logview/webroot;

        # HTTP Basic Authentication
        auth_basic "Restricted";
        auth_basic_user_file $document_root/../config/htpasswd;

        # If a file exists in the webroot, serve it directly
        # Otherwise, forward the request to the application
        location / {
            index index.php;
            try_files $uri $uri/ /index.php?$query_string;
        }

        # Don't serve .htaccess files
        location ~ /\.ht {
            deny all;
        }

        # Pass all application requests to PHP
        location /index.php {
            fastcgi_index index.php;
            fastcgi_pass unix:/var/run/php5-fpm.sock;
            include /etc/nginx/fastcgi_params;
        }
    }

Note that serving this application out of an aliased subdirectory is not a good idea, due to a longstanding bug with the `try_files` and `alias` directives.

Configureing logview
--------------------

The configuration file for logview is located in `config/config.json`.

### logdir

Required.  Set this to the directory of log files that you would like to serve.
