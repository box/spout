---
layout: page
title:  "Add data to an existing spreadsheet"
category: guide
permalink: /guides/add-data-to-existing-spreadsheet/
---

A common use case when using spreadsheets is to add data to an existing spreadsheet. For instance, let's assume you built a spreadsheet containing the last orders on your favorite website and want to update it as you make a new order.

We'll start with a file called "orders.xlsx" and add a new row, containing the last order's info, at the end.

In order to avoid memory issues when dealing with large spreadsheets, {{ site.spout_html }} does not hold the whole representation of the spreadsheet in memory. So to alter an existing spreadsheet, we'll have to create a new one that is similar to the existing one and add the new data in the new one.

```php
<?php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

$existingFilePath = 'path/to/orders.xlsx';
$newFilePath = 'path/to/new-orders.xlsx';

// we need a reader to read the existing file...
$reader = ReaderEntityFactory::createReaderFromFile($existingFilePath);
$reader->setShouldFormatDates(true); // this is to be able to copy dates
$reader->open($existingFilePath);

// ... and a writer to create the new file
$writer = WriterEntityFactory::createWriterFromFile($newFilePath);
$writer->openToFile($newFilePath);

// let's read the entire spreadsheet...
foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
    // Add sheets in the new file, as we read new sheets in the existing one
    if ($sheetIndex !== 1) {
        $writer->addNewSheetAndMakeItCurrent();
    }

    foreach ($sheet->getRowIterator() as $row) {
        // ... and copy each row into the new spreadsheet
        $writer->addRow($row);
    }
}

// At this point, the new spreadsheet contains the same data as the existing one.
// So let's add the new data:
$writer->addRow(
    WriterEntityFactory::createRowFromArray(['2015-12-25', 'Christmas gift', 29, 'USD'])
);

$reader->close();
$writer->close();
```

Optionally, if you rely on the file name or want to keep only one file, simple remove the old file and rename the new one:

```php?start_inline=1
unlink($existingFilePath);
rename($newFilePath, $existingFilePath);
```

That's it! The created file now contains the updated data and is ready to be used.
