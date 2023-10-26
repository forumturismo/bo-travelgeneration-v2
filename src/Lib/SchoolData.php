<?php

namespace App\Lib;

class SchoolData
{
    public $value;
    public $name;

    public function __construct(string $value, string $name)
    {
        $this->value    = $value;
        $this->name     = $name;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param mixed string
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

}