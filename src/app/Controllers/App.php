<?php
namespace App\Controllers;

use App\Models\Options;

class App extends Controller {

    public function index()
    {
        $this->f3->get('twig')->display('App.twig');
    }

    public function docs()
    {
        $this->f3->get('twig')->display('Docs.twig');
    }

    public function setup()
    {
        // Setup panel for testing purposes. Clicking buttons doing the same actions as issuing API requests.

        $options = new Options();
        $options->load(array('name=?','DB_VERSION'));

        $this->f3->get('twig')->display(
            'Setup.twig', 
            [
                'system_installed' => $options->created,
                'api_database_version' => $options->value,
                'api_database_updated' => $options->updated,
            ]
        );
    }

}