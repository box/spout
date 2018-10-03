---
layout: doc
title: Documentation
permalink: /docs/
---

## Configuration for CSV

It is possible to configure both the CSV reader and writer to adapt them to your requirements:

```php
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Common\Type;

$reader = ReaderEntityFactory::createReader(Type::CSV);
/** All of these methods have to be called before opening the reader. */
$reader->setFieldDelimiter('|');
$reader->setFieldEnclosure('@');

```

Additionally, if you need to read non UTF-8 files, you can specify the encoding of your file this way:

```php
$reader->setEncoding('UTF-16LE');
```

By default, the writer generates CSV files encoded in UTF-8, with a BOM.
It is however possible to not include the BOM:

```php
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Type;

$writer = WriterEntityFactory::createWriter(Type::CSV);

$writer->setShouldAddBOM(false);
```


## Configuration for XLSX and ODS

### New sheet creation

It is possible to change the behavior of the writers when the maximum number of rows (*1,048,576*) has been written in the current sheet. By default, a new sheet is automatically created so that writing can keep going but that may not always be preferable.

```php

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Type;

$writer = WriterEntityFactory::createWriter(Type::ODS);
$writer->setShouldCreateNewSheetsAutomatically(true); // default value
$writer->setShouldCreateNewSheetsAutomatically(false); // will stop writing new data when limit is reached
```

### Using a custom temporary folder

Processing XLSX and ODS files requires temporary files to be created. By default, {{ site.spout_html }} will use the system default temporary folder (as returned by `sys_get_temp_dir()`). It is possible to override this by explicitly setting it on the reader or writer:

```php
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Type;

$writer = WriterEntityFactory::createWriter(Type::XLSX);
$writer->setTempFolder($customTempFolderPath);
```

### Strings storage (XLSX writer)

XLSX files support different ways to store the string values:
* Shared strings are meant to optimize file size by separating strings from the sheet representation and ignoring strings duplicates (if a string is used three times, only one string will be stored)
* Inline strings are less optimized (as duplicate strings are all stored) but is faster to process

In order to keep the memory usage really low, {{ site.spout_html }} does not de-duplicate strings when using shared strings. It is nevertheless possible to use this mode.

```php
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Type;

$writer = WriterEntityFactory::createWriter(Type::XLSX);
$writer->setShouldUseInlineStrings(true); // default (and recommended) value
$writer->setShouldUseInlineStrings(false); // will use shared strings
```

> #### Note on Apple Numbers and iOS support
>
> Apple's products (Numbers and the iOS previewer) don't support inline strings and display empty cells instead. Therefore, if these platforms need to be supported, make sure to use shared strings!

### Date/Time formatting

When reading a spreadsheet containing dates or times, {{ site.spout_html }} returns the values by default as DateTime objects.
It is possible to change this behavior and have a formatted date returned instead (e.g. "2016-11-29 1:22 AM"). The format of the date corresponds to what is specified in the spreadsheet.

```php
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Common\Type;

$reader = ReaderEntityFactory::createReader(Type::XLSX);
$reader->setShouldFormatDates(false); // default value
$reader->setShouldFormatDates(true); // will return formatted dates
```


## Styling rows

It is possible to apply some formatting options to a row. {{ site.spout_html }} supports fonts, background, borders as well as alignment styles.

```php
use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Common\Entity\Style\Color;

$writer = WriterEntityFactory::createWriter(Type::XLSX);
$writer->openToFile($filePath);

/** Create a style with the StyleBuilder */
$style = (new StyleBuilder())
           ->setFontBold()
           ->setFontSize(15)
           ->setFontColor(Color::BLUE)
           ->setShouldWrapText()
           ->setBackgroundColor(Color::YELLOW)
           ->build();

$cells = [
    WriterEntityFactory::createCell('Carl'),
    WriterEntityFactory::createCell('is'),
    WriterEntityFactory::createCell('great!'),
];

/** Create a row with cells and the style*/
$row = WriterEntityFactory::createRow($cells, $style);

/** Add the row to the writer */
$writer->addRow($row);
$writer->close();
```

Adding borders to a row requires a ```Border``` object.

```php
use Box\Spout\Common\Type;
use Box\Spout\Common\Entity\Style\Border;
use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

$border = (new BorderBuilder())
    ->setBorderBottom(Color::GREEN, Border::WIDTH_THIN, Border::STYLE_DASHED)
    ->build();

$style = (new StyleBuilder())
    ->setBorder($border)
    ->build();

$writer = WriterEntityFactory::createWriter(Type::XLSX);
$writer->openToFile($filePath);

$row = WriterEntityFactory::createRowFromArray(['Border Bottom Green Thin Dashed']);
$row->setStyle($style);
$writer->addRow($row);

$writer->close();
```

{{ site.spout_html }} will use a default style for all created rows. This style can be overridden this way:

```php
$defaultStyle = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(11)
                ->build();

$writer = WriterEntityFactory::createWriter(Type::XLSX);
$writer->setDefaultRowStyle($defaultStyle)
       ->openToFile($filePath);
```

Unfortunately, {{ site.spout_html }} does not support all the possible formatting options yet. But you can find the most important ones:

| Category  | Property      | API
|:----------|:--------------|:--------------------------------------
| Font      | Bold          | `StyleBuilder::setFontBold()`
|           | Italic        | `StyleBuilder::setFontItalic()`
|           | Underline     | `StyleBuilder::setFontUnderline()`
|           | Strikethrough | `StyleBuilder::setFontStrikethrough()`
|           | Font name     | `StyleBuilder::setFontName('Arial')`
|           | Font size     | `StyleBuilder::setFontSize(14)`
|           | Font color    | `StyleBuilder::setFontColor(Color::BLUE)`<br>`StyleBuilder::setFontColor(Color::rgb(0, 128, 255))`
| Alignment | Wrap text     | `StyleBuilder::setShouldWrapText(true|false)`


## Styling cells

The same styling techniques as described in [Styling rows](#styling-rows) can be applied to individual cells of a row as well.

Cell styles are inherited from the parent row and the default row style respectively.

The styles applied to a specific cell will override any parent styles if present.

Example:

```php
use Box\Spout\Common\Type;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

$defaultStyle = (new StyleBuilder())
    ->setFontSize(8)
    ->build();

$writer = WriterEntityFactory::createWriter(Type::XLSX);
$writer->setDefaultRowStyle($defaultStyle)
    ->openToFile($filePath);

$zebraBlackStyle = (new StyleBuilder())
    ->setBackgroundColor(Color::BLACK)
    ->setFontColor(Color::WHITE)
    ->setFontSize(10)
    ->build();

$zebraWhiteStyle = (new StyleBuilder())
    ->setBackgroundColor(Color::WHITE)
    ->setFontColor(Color::BLACK)
    ->setFontItalic()
    ->build();  

$cells = [
    WriterEntityFactory::createCell('Ze', $zebraBlackStyle),
    WriterEntityFactory::createCell('bra', $zebraWhiteStyle)
];

$rowStyle = (new StyleBuilder())
    ->setFontBold()
    ->build();

$row = WriterEntityFactory::createRow($cells, $rowStyle);

$writer->addRow($row);
$writer->close();
```

## Playing with sheets

When creating a XLSX or ODS file, it is possible to control which sheet the data will be written into. At any time, you can retrieve or set the current sheet:

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

If you rely on the sheet's name in your application, you can access it and customize it this way:

```php
// Accessing the sheet name when reading
foreach ($reader->getSheetIterator() as $sheet) {
    $sheetName = $sheet->getName();
}

// Accessing the sheet name when writing
$sheet = $writer->getCurrentSheet();
$sheetName = $sheet->getName();

// Customizing the sheet name when writing
$sheet = $writer->getCurrentSheet();
$sheet->setName('My custom name');
```

> Please note that Excel has some restrictions on the sheet's name:
> * it must not be blank
> * it must not exceed 31 characters
> * it must not contain these characters: \ / ? * : [ or ]
> * it must not start or end with a single quote
> * it must be unique
>
> Handling these restrictions is the developer's responsibility. {{ site.spout_html }} does not try to automatically change the sheet's name, as one may rely on this name to be exactly what was passed in.

Finally, it is possible to know which sheet was active when the spreadsheet was last saved. This can be useful if you are only interested in processing the one sheet that was last focused.

```php
foreach ($reader->getSheetIterator() as $sheet) {
    // only process data for the active sheet
    if ($sheet->isActive()) {
        // do something...
    }
}
```

## Fluent interface

Because fluent interfaces are great, you can use them with {{ site.spout_html }}:

```php
use Box\Spout\Writer\WriterEntityFactory;
use Box\Spout\Common\Type;

$writer = WriterEntityFactory::create(Type::XLSX);
$writer->setTempFolder($customTempFolderPath)
       ->setShouldUseInlineStrings(true)
       ->openToFile($filePath)
       ->addRow($headerRow)
       ->addRows($dataRows)
       ->close();
```
