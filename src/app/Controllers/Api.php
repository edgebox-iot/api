<?php
namespace App\Controllers;

class App extends Controller {

    public function index()
    {
        $this->f3->get('twig')->display('App.twig');
    }

    public function docs()
    {
        $this->f3->get('twig')->display('Docs.twig');
    }

}