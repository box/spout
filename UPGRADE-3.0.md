Upgrading from 2.x to 3.0
=========================

Spout 3.0 introduced several backwards-incompatible changes. The upgrade from Spout 2.x to 3.0 must therefore be done with caution.
This guide is meant to ease this process.

Most notable changes
--------------------
In 2.x, styles were applied per row; it was therefore impossible to apply different styles to cells in the same row.
With the 3.0 version, this is now possible: each cell can have its own style.

Spout 3.0 tries to enforce better typing. For instance, instead of using/returning generic arrays, Spout now makes use of specific `Row` and `Cell` objects that can encapsulate more data such as type, style, value.

Finally, **_Spout 3.0 only supports PHP 7.1 and above_**, as other PHP versions are no longer supported by the community.

Reader changes
--------------
Creating a reader should now be done through the Reader `ReaderEntityFactory`, instead of using the `ReaderFactory`.
Also, the `ReaderFactory::create($type)` method was removed and replaced by methods for each reader:
```php
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory; // namespace is no longer "Box\Spout\Reader"
...
$reader = ReaderEntityFactory::createXLSXReader(); // replaces ReaderFactory::create(Type::XLSX)
$reader = ReaderEntityFactory::createCSVReader();  // replaces ReaderFactory::create(Type::CSV)
$reader = ReaderEntityFactory::createODSReader();  // replaces ReaderFactory::create(Type::ODS)
```

When iterating over the spreadsheet rows, Spout now returns `Row` objects, instead of an array containing row values. Accessing the row values should now be done this way:
```php
...
foreach ($reader->getSheetIterator() as $sheet) {
    foreach ($sheet->getRowIterator() as $row) { // $row is a "Row" object, not an array
        $rowAsArray = $row->toArray();  // this is the 2.x equivalent
        // OR
        $cellsArray = $row->getCells(); // this can be used to get access to cells' details
        ... 
    }
}
```

Writer changes
--------------
Writer creation follows the same change as the reader. It should now be done through the Writer `WriterEntityFactory`, instead of using the `WriterFactory`.
Also, the `WriterFactory::create($type)` method was removed and replaced by methods for each writer:

```php
use Box\Spout\Writer\Common\Creator\WriterEntityFactory; // namespace is no longer "Box\Spout\Writer"
...
$writer = WriterEntityFactory::createXLSXWriter(); // replaces WriterFactory::create(Type::XLSX)
$writer = WriterEntityFactory::createCSVWriter();  // replaces WriterFactory::create(Type::CSV)
$writer = WriterEntityFactory::createODSWriter();  // replaces WriterFactory::create(Type::ODS)
```

Adding rows is also done differently: instead of passing an array, the writer now takes in a `Row` object (or an array of `Row`). Creating such objects can easily be done this way:
```php
// Adding a row from an array of values (2.x equivalent)
$cellValues = ['foo', 12345];
$row1 = WriterEntityFactory::createRowFromArray($cellValues, $rowStyle);

// Adding a row from an array of Cell
$cell1 = WriterEntityFactory::createCell('foo', $cellStyle1); // this cell has its own style
$cell2 = WriterEntityFactory::createCell(12345, $cellStyle2); // this cell has its own style
$row2 = WriterEntityFactory::createRow([$cell1, $cell2]);

$writer->addRows([$row1, $row2]);
```

Namespace changes for styles
-----------------
The namespaces for styles have changed. Styles are still created by using a `builder` class.

For the builder, please update your import statements to use the following namespaces:

    Box\Spout\Writer\Common\Creator\Style\StyleBuilder
    Box\Spout\Writer\Common\Creator\Style\BorderBuilder

The `Style` base class and style definitions like `Border`, `BorderPart` and `Color` also have a new namespace.

If your are using these classes directly via an import statement in your code, please use the following namespaces:

    Box\Spout\Common\Entity\Style\Border
    Box\Spout\Common\Entity\Style\BorderPart
    Box\Spout\Common\Entity\Style\Color
    Box\Spout\Common\Entity\Style\Style

Handling of empty rows
----------------------
In 2.x, empty rows were not added to the spreadsheet.
In 3.0, `addRow` now always writes a row to the spreadsheet: when the row does not contain any cells, an empty row is created in the sheet.
