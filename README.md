CIS 485 - Senior Project
------------------------
###### Professor: Dr. Ben Blake; Spring 2016

This is a repository for all projects and assingments done in CIS 485.

> Design and implement a large group project. The project will be based on knowledge and skills acquired throughout the students' tenure as a CIS or CSC major. Presentations and accompanying reports are required. Upon successful completion of this course, a student will have learned to work effectively on a large project within a group setting, and will have gained experience in reporting on the project during its various stages of development. In doing so, the student will also gain understanding through experience, of the important phases of project development- planning,analysis, design, implementation, and testing. The project will be a substantial addition to the student's portfolio.

##inc/config.php
The secure configuration file we use is not provided to the public, but an example is provided below. These are globally defined for ease-of-use with other pages. Any page that needs these values (typically only those in `/inc`) can call `require_once 'inc/config.php';` but most functions that need these values have their own scripts to include (ie: `sql_connection.php`, `sendmail.php`, etc.)

```php
/* Enable errors */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Google reCAPTCHA secret */
define('RECAPTCHA_SECRET',  'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX_XXXXXXXXX');

/* MySQL Database Settings */
define('SQL_TYPE',          'mysql');
define('SQL_HOST',          'localhost');
define('SQL_PORT',          '3306');
define('SQL_DB',            'database');
define('SQL_USER',          'username');
define('SQL_PASSWD',        'c00lPassw0rd!');
```

##Credits
Please see [humans.txt](humans.txt) for all contributors.