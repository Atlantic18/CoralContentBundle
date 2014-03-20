<?php

namespace Coral\ContentBundle\Tests\Entity;

use Coral\ContentBundle\Entity\Content;

class ContentTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneratePermidExists()
    {
        $content = new Content;
        $content->setPermid('new');

        $content->generatePermid();

        $this->assertEquals('new', $content->getPermid());
    }

    public function testGeneratePermid()
    {
        $content = new Content;
        $content->generatePermid();

        $this->assertEquals(24, strlen($content->getPermid()));
        $this->assertRegExp('/^[a-zA-Z][0-9a-zA-Z]+$/i', $content->getPermid());
    }

    protected function tearDown() {
        $i = 0;
        do {
            $this->runTest(); // Re-run the test
            $i++;
        } while($i < 100);
    }
}