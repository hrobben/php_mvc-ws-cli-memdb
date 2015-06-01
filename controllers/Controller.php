<?php

class Controller
{
    public function __construct($queryParams, $extraData)
    {
        $this->queryParams = $queryParams;
        $this->extraData = $extraData;
    }
}
