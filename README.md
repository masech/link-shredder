# link-shredder

Link-shredder is a web application for shortening URLs.

The basic principle of its work is to insert the original URL into the database, converting ID from the database into a path of short link and reverse the conversion for redirection.

The maximal number of posible short links is limited by the numeric type of ID-column for the database table. By default, the number is 2147483647. Since this app is written in PHP, in this implementation you will need the GMP library to enable functions for ID conversion to path and reverse.

See the demo at: [https://li-sh.herokuapp.com/](https://li-sh.herokuapp.com/)

## Installation

Install app with:

```
$ git clone https://github.com/masech/link-shredder
$ composer install
```

## Before Usage

You will need to create a database with only one table named "links":

| id | link |
|----|------|
|    |      |

Where:
- id   - column with autoincrementing integer (PRIMARY KEY)
- link - column with text (length >= 2048)

If you use PostgreSQL, this can be done with:

```
$ psql < init.sql
```

Then edit the file "link-shredder/config/db.php" with real: dsn, username and password.

Also do not forget to install the GMP library, for example in Ubuntu:

```
$ sudo apt install php-gmp
```

## Startup

In the directory "link-shredder/public" run the command:

```bash
$ php -S localhost:8080
```