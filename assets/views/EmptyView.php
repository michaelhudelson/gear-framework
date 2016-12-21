<?php

namespace GearFramework;

class View
{
    protected $model;
 
    public function __construct($model)
    {
        $this->model = $model;
    }
 
    public function output()
    {
        return;
    }
}
