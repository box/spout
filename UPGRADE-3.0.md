# Migrate from 2.x to 3.0

## Handling of empty rows

* The handling of empty data in writers has changed. In 2.x an array was not added to the spreadsheet when ```empty($dataRow)``` evaluated to true.
* In 3.0 a row is always written to the speadsheet. When the row does not contain any cells an empty row is created in the sheet.
