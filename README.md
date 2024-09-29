Karrier Manufaktúra
========================
Requirements
------------

* PHP 8.2.0 or higher;
* PDO-SQLite PHP extension enabled;
* and the [usual Symfony application requirements][2].

Installation And Usage
------------
**Composer install**
```bash
composer install
```

**Symfony-cli**

[Download Symfony CLI][2]
```bash
scoop install symfony-cli
```

**[Run Symfony Local Web Server][3]**

In the project directory:
```bash
symfony server:start
```

**[Build CSS From Sass][4]**
```bash
php bin/console sass:build
php bin/console sass:build --watch
```

**[Compile Asset Map For Deploying][5]**
```bash
php bin/console asset-map:compile
```

[1]: https://symfony.com/doc/current/setup.html#technical-requirements
[2]: https://symfony.com/download
[3]: https://symfony.com/doc/current/setup/symfony_server.html#getting-started
[4]: https://symfony.com/bundles/SassBundle/current/index.html#usage
[5]: https://symfony.com/bundles/SassBundle/current/index.html#deploying