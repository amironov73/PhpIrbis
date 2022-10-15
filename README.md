# PhpIrbis

Universal client software for IRBIS64 library automation system (ManagedIrbis ported to PHP 5). Available on [Packagist](https://packagist.org/packages/amironov73/phpirbis).

[![Latest Stable Version](https://poser.pugx.org/amironov73/phpirbis/v/stable)](https://packagist.org/packages/amironov73/phpirbis) 
[![Total Downloads](https://poser.pugx.org/amironov73/phpirbis/downloads)](https://packagist.org/packages/amironov73/phpirbis)
[![Latest Unstable Version](https://poser.pugx.org/amironov73/phpirbis/v/unstable)](https://packagist.org/packages/amironov73/phpirbis)
[![Monthly Downloads](https://poser.pugx.org/amironov73/phpirbis/d/monthly)](https://packagist.org/packages/amironov73/phpirbis)

Now supported PHP 5.4+ on Windows (Open Server), MacOS X (MAMP and MAMP Pro) and Ubuntu Linux.

![phpstorm](Docs/img/phpstorm.png)

```php
require __DIR__ . '/../vendor/autoload.php';

$connection = new Irbis\Connection();
$connectString = 'host=127.0.0.1;user=librarian;password=secret;';
$connection->parseConnectionString($connectString);

if (!$connection->connect()) {
    echo "Can't connect!\n";
    echo Irbis\describe_error($connection->lastError);
    die(1);
}

$found = $connection->search('"A=Byron, George$"');
echo "<p>Records found: " . count($found) . "</p>\n";

foreach ($found as $mfn) {
    $record = $connection->readRecord($mfn);

    $title = $record->fm(200, 'a');
    echo "<p><b>Title:</b> {$title}<br/>";

    $description = $connection->formatRecord("@brief", $mfn);
    echo "<b>Description:</b> {$description}</p>\n";
}

$connection->disconnect();
```

### Documentation (in russian)

[![Badge](https://readthedocs.org/projects/phpirbis/badge/)](https://phpirbis.readthedocs.io/) 
