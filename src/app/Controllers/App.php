<?php
namespace App\Controllers;

use App\Models\Options;
use App\Helper\EdgeboxioApiConnector;

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

    public function access() {

        $options = new Options();
        $edgeboxio_api = new EdgeboxioApiConnector();

        $status = 'Waiting for Edgebox.io Account Credentials';

        if(!empty($this->f3->get('POST.username')) && !empty($this->f3->get('POST.password'))) {
            // User submitted login credentials for API.
            $api_token = $edgeboxio_api->get_token($this->f3->get('POST.username'), $this->f3->get('POST.password'));
            print_r($api_token);
            if($api_token['status'] == 'success') {
                
                // Save API token in database, for future requests.
                $options->load(array('name=?','EDGEBOXIO_API_TOKEN'));
                $options->name = 'EDGEBOXIO_API_TOKEN';
                $options->value = $api_token['value'];
                $options->save();

                // TODO: Request Edgebox.io API information about the bootnode.


            } else {

                $status = $api_token['value'];
            
            }
        }

        $options = new Options();
        $options->load(array('name=?','EDGEBOXIO_API_TOKEN'));  
        $show_form  = true;
        $api_token = '';

        if(!empty($options->value)) {
            $status = "Logged in to Edgebox.io";
            $show_form = false;
            $api_token = $options->value;

        }

        $this->f3->get('twig')->display(
            'Access.twig', 
            [
                'show_form' => $show_form,
                'status' => $status,
                'api_token' => $api_token,
            ]
        );

    }

}