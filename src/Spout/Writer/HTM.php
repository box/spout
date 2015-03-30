<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Exception\IOException;

/**
 * Class HTM
 * This class provides support to write data to HTM files
 *
 * @package Box\Spout\Writer
 */
class HTM extends AbstractWriter
{
    /** Number of rows to write before flushing */
    const FLUSH_THRESHOLD = 500;

    /** @var string Content-Type value for the header */
    protected static $headerContentType = 'text/html; charset=UTF-8';

    /** @var int */
    protected $lastWrittenRowIndex = 0;

    /**
     * Opens the HTM streamer and makes it ready to accept data.
     *
     * @return void
     */
    protected function openWriter()
    {
        fwrite($this->filePointer, "<html>\n");
        fwrite($this->filePointer, "<head>\n");
        fwrite($this->filePointer, "<title>" . htmlentities(basename(basename($this->outputFilePath, '.html'), '.htm')) . "</title>\n");
        fwrite($this->filePointer, "
<style>
table {
    border-collapse: collapse;
    width: 100%;
    position: relative;
}
td, th {
    padding: 10px 25px;
    border: 1px solid #000;
}
th {
    color: #fff;
    background: #500;
    white-space: nowrap;
}
th span {
    visibility: hidden;
}
th div {
    position: fixed;
    top: 20px;
    background: #500;
}
td {
    color: #000;
    background: #ddd;
}
</style>

<script>
window.onscroll=function() {
    var ths=document.getElementsByTagName('div');
    for (var i=0;i<ths.length;i++)
    {
        ths[i].style.marginLeft=(-window.scrollX)+'px';
    }
};
</script>
\n");
        fwrite($this->filePointer, "</head>\n");
        fwrite($this->filePointer, "<body>\n");
        fwrite($this->filePointer, "<table>\n");
    }

    /**
     * Adds data to the currently opened writer.
     *
     * @param  array $dataRow Array containing data to be written.
     *          Example $dataRow = ['data1', 1234, null, '', 'data5'];
     * @param  array $metaData Array containing meta-data maps for individual cells, such as 'url'
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If unable to write data
     */
    protected function addRowToWriter(array $dataRow, array $metaData)
    {
        $wasWriteSuccessful = true;
        if ($this->lastWrittenRowIndex == 0) {
            $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, "<thead>\n");
        }
        if ($this->lastWrittenRowIndex == 1) {
            $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, "<tbody>\n");
        }
        $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, "<tr>\n");
        foreach ($dataRow as $i => $cell) {
            $cell = nl2br(htmlentities($cell));

            if (isset($metaData[$i]['url']))
            {
                $cell = '<a href="' . htmlentities($metaData[$i]['url']) . '">' . $cell . '</a>';
            }

            if ($this->lastWrittenRowIndex == 0) {
                $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, "\t<th><span>{$cell}</span><div>{$cell}</div></th>\n");
            } else
            {
                $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, "\t<td>{$cell}</td>\n");
            }
        }
        $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, "</tr>\n");
        if ($this->lastWrittenRowIndex == 0) {
            $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, "</thead>\n");
        }

        if ($wasWriteSuccessful === false) {
            throw new IOException('Unable to write data');
        }

        $this->lastWrittenRowIndex++;
        if ($this->lastWrittenRowIndex % self::FLUSH_THRESHOLD === 0) {
            $this->globalFunctionsHelper->fflush($this->filePointer);
        }
    }

    /**
     * Closes the HTM streamer, preventing any additional writing.
     * If set, sets the headers and redirects output to the browser.
     *
     * @return void
     */
    protected function closeWriter()
    {
        if ($this->filePointer) {
            if ($this->lastWrittenRowIndex >= 1) {
                fwrite($this->filePointer, "</tbody>\n");
            }
            fwrite($this->filePointer, "</table>\n");
            fwrite($this->filePointer, "</body>\n");
            fwrite($this->filePointer, "</html>\n");

            $this->globalFunctionsHelper->fclose($this->filePointer);
        }

        $this->lastWrittenRowIndex = 0;
    }
}
