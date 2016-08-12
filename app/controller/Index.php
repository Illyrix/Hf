<?php

namespace app\controller;

use core\system\Controller;

class Index extends Controller
{
    public function index() {
        $this->_view->render(Array('title' => 'Hello world!', 'content' => 'Hf works! +1s'));
        $this->_view->display('index');
    }
}