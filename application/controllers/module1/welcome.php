<?php

class Welcome extends CI_Controller
{
    public function index()
    {
        $this->stdreturn->ok($this->params);
    }
}
