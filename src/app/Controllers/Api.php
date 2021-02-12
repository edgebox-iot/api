<?php
namespace App\Controllers;

class Api extends Controller {

    public function index()
    {
        $this->f3->get('twig')->display('App.twig');
    }

}