<?php
namespace App\Controllers;

use App\Models\Options;
use App\Models\Tasks;
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
        $connection_status = 'Not connected';
        $connection_details = [];

        if(!empty($this->f3->get('POST.username')) && !empty($this->f3->get('POST.password'))) {
            // User submitted login credentials for API.
            $api_token = $edgeboxio_api->get_token($this->f3->get('POST.username'), $this->f3->get('POST.password'));
            if($api_token['status'] == 'success') {
                
                // Save API token in database, for future requests.
                $options->load(array('name=?','EDGEBOXIO_API_TOKEN'));
                $options->name = 'EDGEBOXIO_API_TOKEN';
                $options->value = $api_token['value'];
                $options->save();

                // Request Edgebox.io API information about the bootnode.
                $tunnel_info = $edgeboxio_api->get_bootnode_info();

                if($tunnel_info['status'] == 'success') {

                    $options->load(array('name=?','BOOTNODE_ADDRESS'));
                    $options->name = 'BOOTNODE_ADDRESS';
                    $options->value = $tunnel_info['value']['bootnode_address'];
                    $options->save();

                    $options->load(array('name=?','BOOTNODE_TOKEN'));
                    $options->name = 'BOOTNODE_TOKEN';
                    $options->value = $tunnel_info['value']['bootnode_token'];
                    $options->save();

                    $options->load(array('name=?','BOOTNODE_ASSIGNED_ADDRESS'));
                    $options->name = 'BOOTNODE_ASSIGNED_ADDRESS';
                    $options->value = $tunnel_info['value']['assigned_address'];
                    $options->save();

                    // Issue tasks for SysCtl to setup the tunnel connection to myedge.app service.
                    $tasks = new Tasks();
                    $tasks->task = 'setup_tunnel';
                    $tasks->args = json_encode($tunnel_info['value']);
                    $tasks->save();

                    $connection_status = "Configuring tunnel network...";
                    $connection_details = $tunnel_info['value'];

                } else {

                    $status = json_encode($tunnel_info['value']);
                
                }

                
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
                'connection_status' => $connection_status,
                'connection_details' => $connection_details,
                'api_token' => $api_token,
            ]
        );

    }

    public function access_logout() {

        $options = new Options();
        $options->load(array('name=?', 'EDGEBOXIO_API_TOKEN'));
        $options->name='EDGEBOXIO_API_TOKEN';
        $options->value = '';
        $options->save();
        $this->f3->reroute('/setup/access');

    }

}