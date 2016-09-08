<?php
namespace BittyPHP\Thing\Test;

class SampleModel
{
    /**
     * The source data
     *
     * @var array
     */
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function loader()
    {
        return $this->data;
    }

    public function filter()
    {
        $data['c'] = false;
        return $data;
    }
}
