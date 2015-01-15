# Spout

Spout is a PHP library to read and write CSV and XLSX files, in a fast and scalable way.
Contrary to other file readers or writers, it is capable of processing very large files while keeping the memory usage really low (less than 10MB).

[![Build Status](https://travis-ci.org/box/spout.png?branch=master)](http://travis-ci.org/box/spout)
[![Project Status](http://opensource.box.com/badges/active.svg)](http://opensource.box.com/badges)


## Installation

The Spout library can be installed directly from [Composer](https://getcomposer.org/).

Add "box/spout" as a dependency in your project's composer.json file:
```json
"require": {
    "box/spout": "*"
}
```

Then run the install command from Composer:
```
php composer.phar install
```


## Requirements

* PHP version 5.4.0 or higher
* PHP extension `php_zip` enabled
* PHP extension `php_xmlreader` enabled
* PHP extension `php_simplexml` enabled


## Basic usage

### Reader

#### How to read a CSV file?

```php
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

$reader = ReaderFactory::create(Type::CSV);
$reader->open($filePath);

while ($reader->hasNextRow()) {
    $row = $reader->nextRow();
    // do stuff
}

$reader->close();
```

#### How to read a XLSX file?

```php
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

$reader = ReaderFactory::create(Type::XLSX);
$reader->open($filePath);

while ($reader->hasNextSheet()) {
    $reader->nextSheet();

    while ($reader->hasNextRow()) {
        $row = $reader->nextRow();
        // do stuff
    }
}

$reader->close();
```

If there are multiple sheets in the file, the reader will read through all of them sequentially.

### Writer

### How to create a CSV file?

```php
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

$writer = WriterFactory::create(Type::CSV);
$writer->openToFile($filePath); // write data to a file or to a PHP stream
$writer->addRow($singleRow); // add a row at a time
$writer->close();
```

### How to create a XLSX file?

```php
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

$writer = WriterFactory::create(Type::XLSX);
$writer->openToBrowser($fileName); // stream data directly to the browser
$writer->addRows($multipleRows); // add multiple rows at a time
$writer->close();
```

For XLSX files, the number of rows per sheet is limited to 1,048,576 (see [Office OpenXML specs](http://office.microsoft.com/en-us/excel-help/excel-specifications-and-limits-HP010073849.aspx)). By default, once this limit is reached, the writer will automatically create a new sheet and continue writing data into it.


## Advanced usage

### Configuring the CSV reader and writer

It is possible to configure the both the CSV reader and writer to specify the field separator as well as the field enclosure:
```php
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

$reader = ReaderFactory::create(Type::CSV);
$reader->setFieldDelimiter('|');
$reader->setFieldEnclosure('@');
```

### Configuring the XLSX writer

#### Strings storage

XLSX files support different ways to store the string values:
* Shared strings are meant to optimize file size by separating strings from the sheet representation and ignoring strings duplicates (if a string is used three times, only one string will be stored)
* Inline strings are less optimized (as duplicate strings are all stored) but is faster to process

In order to keep the memory usage really low, Spout does not optimize strings when using shared strings. It is nevertheless possible to use this mode.
```php
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

$writer = WriterFactory::create(Type::XLSX);
$writer->setShouldUseInlineStrings(true); // default (and recommended) value
$writer->setShouldUseInlineStrings(false); // will use shared strings
```

#### New sheet creation

It is also possible to change the behavior of the writer when the maximum number of rows (1,048,576) have been written in the current sheet:
```php
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

$writer = WriterFactory::create(Type::XLSX);
$writer->setShouldCreateNewSheetsAutomatically(true); // default value
$writer->setShouldCreateNewSheetsAutomatically(false); // will stop writing new data when limit is reached
```

### Using custom temporary folder

Processing XLSX files require temporary files to be created. By default, Spout will use the system default temporary folder (as returned by sys_get_temp_dir()). It is possible to override this by explicitly setting it on the reader or writer:
```php
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

$writer = WriterFactory::create(Type::XLSX);
$writer->setTempFolder($customTempFolderPath);
```

### Playing with XLSX sheets

When creating a XLSX file, it is possible to control in which sheet the data will be written to.
At any point, you can retrieve the current sheet and set the current sheet:
```php
$firstSheet = $writer->getCurrentSheet();
$writer->addRow($rowForSheet1); // writes the row to the first sheet

$newSheet = $writer->addNewSheetAndMakeItCurrent();
$writer->addRow($rowForSheet2); // writes the row to the new sheet

$writer->setCurrentSheet($firstSheet);
$writer->addRow($anotherRowForSheet1); // append the row to the first sheet
```

It is also possible to retrieve all the sheets currently created:
```php
$sheets = $writer->getSheets();
```

### Fluent interface

Because fluent interfaces are great, you can use them with Spout:
```php
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

$writer = WriterFactory::create(Type::XLSX);
$writer->setTempFolder($customTempFolderPath)
       ->setShouldUseInlineStrings(true)
       ->openToFile($filePath)
       ->addRow($headerRow)
       ->addRows($dataRows)
       ->close();
```


## Running tests

On the `master` branch, only unit and functional tests are included. The performance requires very large files and have been excluded.
If you just want to check that everything is working as expected, executing the tests of the master branch is enough.

If you want to run performance tests, you will need to checkout the `perf-tests` branch. Multiple test suites can then be run, depending on the expected output:

* `phpunit` - runs the whole test suite (unit + functional + performance tests)
* `phpunit --testuite no-perf-tests` - only runs the unit and functional tests
* `phpunit --testuite perf-tests` - only runs the performance tests

For information, the performance tests take about one hour to run (processing 2 million rows files is not a quick thing).


## Frequently Asked Questions

#### How can Spout handle such large data sets and still use less than 10MB of memory?

When writing data, Spout is streaming the data to files, one or few lines at a time. That means that it only keeps in memory the few rows that it needs to write. Once written, the memory is freed.

Same goes with reading. Only one row at a time is stored in memory. A special technique is used to handle shared strings in XLSX, storing them into several small temporary files that allows fast access.

#### How long does it take to generate a file with X rows?

Here are a few numbers regarding the performance of Spout:

|                                  | 2,000 rows (6,000 cells) | 200,000 rows (600,000 cells) | 2,000,000 rows (6,000,000 cells) |
| :------------------------------- | :----------------------: | :--------------------------: | :------------------------------: |
| Read CSV                         | < 1 second               | 4 seconds                    | 2-3 minutes                      |
| Write CSV                        | < 1 second               | 2 seconds                    | 2-3 minutes                      |
| Read XLSX (using inline strings) | < 1 second               | 35-40 seconds                | 18-20 minutes                    |
| Read XLSX (using shared strings) | 1 second                 | 1-2 minutes                  | 35-40 minutes                    |
| Write XLSX                       | 1 second                 | 20-25 seconds                | 8-10 minutes                     |


## Support

Need to contact us directly? Email oss@box.com and be sure to include the name of this project in the subject.


## Copyright and License

Copyright 2015 Box, Inc. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
