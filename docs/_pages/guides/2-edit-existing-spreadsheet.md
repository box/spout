---
layout: page
title:  "Edit an existing spreadsheet"
category: guide
permalink: /guides/edit-existing-spreadsheet/
---

Editing an existing spreadsheet is a pretty common task that {{ site.spout_html }} is totally capable of doing.

With {{ site.spout_html }}, it is not possible to do things like `deleteRow(3)` or `insertRowAfter(5, $newRow)`. This is because {{ site.spout_html }} does not keep an in-memory representation of the entire spreadsheet, to avoid consuming all the memory available with large spreadsheets. This means, {{ site.spout_html }} does not know how to jump to the 3rd row directly and has especially no way of moving backwards (changing row 3 after having changed row 5). So let's see how this can be done, in a scalable way.

For this example, let's assume we have an existing ODS spreadsheet called "my-music.ods" that looks like this:

| Song title       | Artist          | Album           | Year |
| ---------------- | --------------- | --------------- | ---- |
| Yesterday        | The Beatles     | The White Album | 1968 |
| Yellow Submarine | The Beatles     | Unknown         | 1968 |
| Space Oddity     | David Bowie     | David Bowie     | 1969 |
| Thriller         | Michael Jackson | Thriller        | 1982 |
| No Woman No Cry  | Bob Marley      | Legend          | 1984 |
| Buffalo Soldier  | Bob Marley      | Legend          | 1984 |

> Note that the album for "Yellow Submarine" is "Unknown" and that the songs are ordered by year (most recent last).

We'd like to update the missing album for "Yellow Submarine", remove the Bob Marley's songs and add a new song: "Hotel California" from "The Eagles", released in 1976. Here is how this can be done:

```php
<?php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

$existingFilePath = '/path/to/my-music.ods';
$newFilePath = '/path/to/my-new-music.ods';

// we need a reader to read the existing file...
$reader = ReaderEntityFactory::createReaderFromFile($existingFilePath);
$reader->open($existingFilePath);
$reader->setShouldFormatDates(true); // this is to be able to copy dates

// ... and a writer to create the new file
$writer = WriterEntityFactory::createWriterFromFile($newFilePath);
$writer->openToFile($newFilePath);

// let's read the entire spreadsheet
foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
    // Add sheets in the new file, as you read new sheets in the existing one
    if ($sheetIndex !== 1) {
        $writer->addNewSheetAndMakeItCurrent();
    }

    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
        $songTitle = $row->getCellAtIndex(0);
        $artist = $row->getCellAtIndex(1);

        // Change the album name for "Yellow Submarine"
        if ($songTitle === 'Yellow Submarine') {
            $row->setCellAtIndex(WriterEntityFactory::createCell('The White Album'), 2);
        }

        // skip Bob Marley's songs
        if ($artist === 'Bob Marley') {
            continue;
        }

        // write the edited row to the new file
        $writer->addRow($row);

        // insert new song at the right position, between the 3rd and 4th rows
        if ($rowIndex === 3) {
            $writer->addRow(
                WriterEntityFactory::createRowFromArray(['Hotel California', 'The Eagles', 'Hotel California', 1976])
            );
        }
    }
}

$reader->close();
$writer->close();
```

Optionally, if you rely on the file name or want to keep only one file, simple remove the old file and rename the new one:

```php?start_inline=1
unlink($existingFilePath);
rename($newFilePath, $existingFilePath);
```

That's it! The created file now contains the updated data and is ready to be used.
