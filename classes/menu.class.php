<?php

class spWxMenu
{
    protected $menu = [];

    public function __construct($menu)
    {
        $this->menu = $menu;
    }

    public function get_current()
    {

    }

    public function get_all()
    {

    }

    protected $ptr = [];

    public function select($ptr)
    {


    }

}


class spWxMenuTree
{
    /**
     * @var spWxMenuNode
     */
    public $root;

    public function __construct()
    {
        $this->root = new spWxMenuNode();
    }
}

class spWxMenuNode
{
    public function addNode()
    {

    }
}