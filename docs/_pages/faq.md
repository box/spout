---
layout: page
title: Frequently Asked Questions
permalink: /faq/
---

### How can {{ site.spout_html }} handle such large data sets and still use less than 3MB of memory?

When writing data, {{ site.spout_html }} is streaming the data to files, one or few lines at a time. That means that it only keeps in memory the few rows that it needs to write. Once written, the memory is freed.

Same goes with reading. Only one row at a time is stored in memory. A special technique is used to handle shared strings in XLSX, storing them - if needed - into several small temporary files that allows fast access.

### How long does it take to generate a file with X rows?

Here are a few numbers regarding the performance of {{ site.spout_html }}:

| Type | Action                        | 2,000 rows (6,000 cells) | 200,000 rows (600,000 cells) | 2,000,000 rows (6,000,000 cells) |
|------|-------------------------------|--------------------------|------------------------------|----------------------------------|
| CSV  | Read                          | < 1 second               | 4 seconds                    | 2-3 minutes                      |
|      | Write                         | < 1 second               | 2 seconds                    | 2-3 minutes                      |
| XLSX | Read<br>*inline&nbsp;strings* | < 1 second               | 35-40 seconds                | 18-20 minutes                    |
|      | Read<br>*shared&nbsp;strings* | 1 second                 | 1-2 minutes                  | 35-40 minutes                    |
|      | Write                         | 1 second                 | 20-25 seconds                | 8-10 minutes                     |
| ODS  | Read                          | 1 second                 | 1-2 minutes                  | 5-6 minutes                      |
|      | Write                         | < 1 second               | 35-40 seconds                | 5-6 minutes                      |

### Does {{ site.spout_html }} support charts or formulas?

No. This is a compromise to keep memory usage low. Charts and formulas requires data to be kept in memory in order to be used.
So the larger the file would be, the more memory would be consumed, preventing your code to scale well.
