---
layout: page
title:  "Read data from a specific sheet only"
category: guide
permalink: /guides/read-data-from-specific-sheet/
---

Even though a spreadsheet contains multiple sheets, you may be interested in reading only one of them and skip the other ones. Here is how you can do it with Spout:

* If you know the name of the sheet

```php?start_inline=1
$reader = ReaderFactory::create(Type:XLSX);
$reader->open($filePath);

foreach ($reader->getSheetIterator() as $sheet) {
    // only read data from "summary" sheet
    if ($sheet->getName() === 'summary') {
        foreach ($sheet->getRowIterator() as $row) {
            // do something with the row
        }
        break; // no need to read more sheets
    }
}

$reader->close();
```

* If you know the position of the sheet

```php?start_inline=1
$reader = ReaderFactory::create(Type:XLSX);
$reader->open($filePath);

foreach ($reader->getSheetIterator() as $sheet) {
    // only read data from 3rd sheet
    if ($sheet->getIndex() === 2) { // index is 0-based
        foreach ($sheet->getRowIterator() as $row) {
            // do something with the row
        }
        break; // no need to read more sheets
    }
}

$reader->close();
```
