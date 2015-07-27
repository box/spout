<?php

namespace Box\Spout\Reader\Wrapper;

use Box\Spout\TestUsingResource;

/**
 * Class SimpleXMLElementTest
 *
 * @package Box\Spout\Reader\Wrapper
 */
class SimpleXMLElementTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @expectedException \Box\Spout\Reader\Exception\XMLProcessingException
     *
     * @return void
     */
    public function testConstructShouldThrowExceptionIfInvalidData()
    {
        $invalidXML = '<invalid><xml></invalid>';
        new SimpleXMLElement($invalidXML);
    }

    /**
     * @return array
     */
    public function dataProviderForTestGetAttribute()
    {
        $xmlWithoutNamespace = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<worksheet foo="bar" type="test" />
XML;

        $xmlWithHalfNamespace = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<worksheet
    xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
    foo="bar" r:type="test" />
XML;

        $xmlWithFullNamespace = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<worksheet
    xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
    r:foo="bar" r:type="test" />
XML;

        return [
            [$xmlWithoutNamespace, null, ['foo' => 'bar', 'type' => 'test']],
            [$xmlWithHalfNamespace, null, ['foo' => 'bar', 'type' => null]],
            [$xmlWithFullNamespace, null, ['foo' => null, 'type' => null]],
            [$xmlWithoutNamespace, 'r', ['foo' => null, 'type' => null]],
            [$xmlWithHalfNamespace, 'r', ['foo' => null, 'type' => 'test']],
            [$xmlWithFullNamespace, 'r', ['foo' => 'bar', 'type' => 'test']],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetAttribute
     *
     * @param string $xml
     * @param string|null $namespace
     * @param array $expectedAttributes
     * @return void
     */
    public function testGetAttribute($xml, $namespace, $expectedAttributes)
    {
        $element = new SimpleXMLElement($xml);

        foreach ($expectedAttributes as $name => $expectedValue) {
            $value = $element->getAttribute($name, $namespace);
            $this->assertEquals($expectedValue, $value);
        }
    }

    /**
     * @return void
     */
    public function testXPath()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<worksheet>
    <sheetData>
        <row r="1">
            <c r="A1"><v>0</v></c>
            <c r="A2"><v>1</v></c>
        </row>
    </sheetData>
</worksheet>
XML;

        $element = new SimpleXMLElement($xml);
        $matchedElements = $element->xpath('//c');

        $this->assertEquals(2, count($matchedElements));
        $this->assertTrue($matchedElements[0] instanceof SimpleXMLElement, 'The SimpleXMLElement should be wrapped');
        $this->assertEquals('A2', $matchedElements[1]->getAttribute('r'));
    }

    /**
     * @return void
     */
    public function testRemoveNodeMatchingXPath()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<worksheet>
    <sheetData>
        <row r="1">
            <c r="A1"><v>0</v></c>
            <c r="A2"><v>1</v></c>
        </row>
    </sheetData>
</worksheet>
XML;

        $element = new SimpleXMLElement($xml);
        $this->assertNotNull($element->getFirstChildByTagName('sheetData'));

        $element->removeNodesMatchingXPath('//sheetData');
        $this->assertNull($element->getFirstChildByTagName('sheetData'));
    }
}
