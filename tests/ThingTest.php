<?php
namespace BittyPHP\Thing\Test;

use BittyPHP\Thing\Thing;

class ThingTest extends \PHPUnit_Framework_TestCase
{
    public $sample = array(
        'a' => 'aa',
        'b' => array(
            'bb' => 'bbb',
        ),
        'c' => true,
    );

    public function testConstructNormal()
    {
        $sample = $this->sample;

        $loader = function () use ($sample) {
            return $sample;
        };

        $filter = function ($data) {
            $data['c'] = false;
            return $data;
        };

        $thing = new Thing($loader, $filter);
        $this->assertFalse($thing['c']);
    }

    public function testConstructCallback()
    {
        $sample = new SampleModel($this->sample);

        $thing = new Thing(array($sample, 'loader'), array($sample, 'filter'));
        $this->assertFalse($thing['c']);
    }

    public function testConstructSingleClass()
    {
        $sample = new SampleModel($this->sample);
        $thing = new Thing($sample);
        $this->assertFalse($thing['c']);
    }
}
