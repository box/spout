---
layout: page
title:  "[Symfony] Stream content of a large spreadsheet"
category: guide
permalink: /guides/symfony-stream-content-large-spreadsheet/
---

> This tutorial is for the PHP framework [Symfony](http://symfony.com/).

The main benefit of streaming content is that this content can be rendered as soon as it is available. No matter how big the content is, the browser will be able to start rendering it as soon as the first byte is sent.

Reading a static spreadsheet to display its content to a user is a great use case for streaming. The spreadsheet can contain from a few rows to thousands of them and we don't want to wait until the whole file has been read (which can take a long time) before showing something to the user. Let's see how [Symfony's StreamedResponse](http://symfony.com/doc/current/components/http_foundation/introduction.html#streaming-a-response) let us easily stream the content of the spreadsheet.

A regular controller usually builds the content to be displayed and encapsulate it into a `Response` object. Everything happens synchronously. Such a controller may look like this:

```php?start_inline=1
class MyRegularController extends Controller
{
    /**
     * @Route("/spreadsheet/read")
     */
    public function readAction()
    {
        $filePath = '/path/to/static/file.xlsx';

        // The content to be displayed has to be built entirely
        // before it can be sent to the browser.
        $content = '';

        $reader = ReaderEntityFactory::createReaderFromFile($filePath);
        $reader->open($filePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            $content .= '<table>';
            foreach ($sheet->getRowIterator() as $row) {
                $content .= '<tr>';
                $content .= implode(array_map(function($cell) {
                    return '<td>' . $cell . '</td>';
                }, $row->getCells()));
                $content .= '</tr>';
            }
            $content .= '</table><br>';
        }

        $reader->close();

        // The response is sent to the browser
        // once the entire file has been read.
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }
}
```

Converting a regular controller to return a `StreamedResponse` is super easy! This is what it looks like after conversion:

```php?start_inline=1
class MyStreamController extends Controller
{
    // See below how it is used.
    const FLUSH_THRESHOLD = 100;

    /**
     * @Route("/spreadsheet/stream")
     */
    public function readAction()
    {
        $filePath = '/path/to/static/file.xlsx';

        // We'll now return a StreamedResponse.
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/html');

        // Instead of a string, the streamed response will execute
        // a callback function to retrieve data chunks.
        $response->setCallback(function() use ($filePath) {
            // Same code goes inside the callback.
            $reader = ReaderEntityFactory::createXLSXReader();
            $reader->open($filePath);

            $i = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                // The main difference with the regular response is
                // that the content is now echo'ed, not appended.
                echo '<table>';
                foreach ($sheet->getRowIterator() as $row) {
                    echo '<tr>';
                    echo implode(array_map(function($cell) {
                        return '<td>' . $cell . '</td>';
                    }, $row->getCells()));
                    echo '</tr>';

                    $i++;
                    // Flushing the buffer every N rows to stream echo'ed content.
                    if ($i % self::FLUSH_THRESHOLD === 0) {
                        flush();
                    }
                }
                echo '</table><br>';
            }

            $reader->close();
        });

        return $response;
    }
}
```
