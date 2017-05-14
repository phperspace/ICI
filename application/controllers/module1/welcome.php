<?php

class Welcome extends CI_Controller
{
    public function index()
    {
        $this->load->library('CRedis', '', 'CRedis');
        $this->CRedis->set('hello', 'hello world!' . time());
        $this->CRedis->switchHost('host1');
        $this->stdreturn->ok($this->CRedis->get('hello'));
    }
}
