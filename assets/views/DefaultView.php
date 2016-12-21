<?php

namespace GearFramework;

class View
{
    protected $model;
 
    public function __construct($model)
    {
        $this->model = $model;
        \Gear::debug('DefaultView.php loaded.');
    }
 
    public function output()
    {
        return;
    }
}
