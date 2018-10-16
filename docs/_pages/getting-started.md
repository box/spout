---
layout: doc
title: Getting Started
permalink: /getting-started/
---

This guide will help you install {{ site.spout_html }} and teach you how to use it.

## Requirements

* PHP version 7.1 or higher
* PHP extension `ext-zip` enabled
* PHP extension `ext-xmlreader` enabled


## Installation

### Composer (recommended)

{{ site.spout_html }} can be installed directly from [Composer](https://getcomposer.org/).

Run the following command:
```powershell
$ composer require box/spout
```

### Manual installation

If you can't use Composer, no worries! You can still install {{ site.spout_html }} manually.

> Before starting, make sure your system meets the [requirements](#requirements).

1. Download the source code from the [Releases page](https://github.com/box/spout/releases)
2. Extract the downloaded content into your project.
3. Add this code to the top controller (e.g. index.php) or wherever it may be more appropriate:

```php
// don't forget to change the path!
require_once '[PATH/TO]/src/Spout/Autoloader/autoload.php';
```


## Basic usage

### Reader

Regardless of the file type, the interface to read a file is always the same:

```php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Common\Type;

$reader = ReaderEntityFactory::createReader(Type::XLSX); // for XLSX files
// $reader = ReaderEntityFactory::createReader(Type::ODS); // for ODS files
// $reader = ReaderEntityFactory::createReader(Type::CSV); // for CSV files

$reader->open($filePath);

foreach ($reader->getSheetIterator() as $sheet) {
    foreach ($sheet->getRowIterator() as $row) {
        // do stuff with the row
    }
}

$reader->close();
```

If there are multiple sheets in the file, the reader will read all of them sequentially.

---

In addition to passing a reader type to ```ReaderEntityFactory::createReader```, it is also possible to provide a path to a file and create the reader.

```php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

$reader = ReaderEntityFactory::createReaderFromFile('/path/to/file.xlsx');
```

### Writer

As with the reader, there is one common interface to write data to a file:

```php
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Type;

$writer = WriterEntityFactory::createWriter(Type::XLSX);
// $writer = WriterEntityFactory::createWriter(Type::ODS);
// $writer = WriterEntityFactory::createWriter(Type::CSV);

$writer->openToFile($filePath); // write data to a file or to a PHP stream
//$writer->openToBrowser($fileName); // stream data directly to the browser


$cells = [
    WriterEntityFactory::createCell('Carl'),
    WriterEntityFactory::createCell('is'),
    WriterEntityFactory::createCell('great!'),
];

/** add a row at a time */
$singleRow = WriterEntityFactory::createRow($cells);
$writer->addRow($singleRow);

/** add multiple rows at a time */
$multipleRows = [
    WriterEntityFactory::createRow($cells),
    WriterEntityFactory::createRow($cells),
];
$writer->addRows($multipleRows); 

/** add a row from an arry of values */
$values = ['Carl', 'is', 'great!'];
$rowFromValues = WriterEntityFactory::createRowFromArray($values);
$writer->addRow($rowFromValues);

$writer->close();
```

For XLSX and ODS files, the number of rows per sheet is limited to *1,048,576*. By default, once this limit is reached, the writer will automatically create a new sheet and continue writing data into it.


## Advanced usage

You can do a lot more with {{ site.spout_html }}! Check out the [full documentation]({{ site.github.url }}/docs/) to learn about all the features.
