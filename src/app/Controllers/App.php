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

    public function setup_applications() {

        $framework_ready = false;
        $apps_list = [];

        // EDGEAPPS_LIST Option is normally written as soon as sysctl boots, frequently (every 30 seconds), and on request via Task.
        $options = new Options();
        $options->load(array('name=?', 'EDGEAPPS_LIST'));

        if(!empty($options->value)) {
            $apps_list = json_decode($options->value, true);
            // print_r($apps_list);
            $framework_ready = true;
        }

        $this->f3->get('twig')->display(
            'Applications.twig', 
            [
                'framework_ready' => $framework_ready,
                'apps_list' => $apps_list,
            ]
        );

    }

    public function set_applications_action() {

        $framework_ready = false;
        $apps_list = [];

        // EDGEAPPS_LIST Option is normally written as soon as sysctl boots, frequently (every 30 seconds), and on request via Task.
        $options = new Options();
        $options->load(array('name=?', 'EDGEAPPS_LIST'));

        if(!empty($options->value)) {
            $apps_list = json_decode($options->value, true);
            // print_r($apps_list);
            $framework_ready = true;
        }

        $found = false;
        $app_current_status = [];

        foreach ($apps_list as $edge_app) {
            if($edge_app['id'] == $this->f3->get('PARAMS.edgeapp')) {
                
                $found = true;
                $app_current_status = $edge_app;

            }
        }

        if($found) {

            switch ($this->f3->get("PARAMS.action")) {
                case 'start':
                    
                    // If status is any other than "off", App must be stopped first. Hence this action acts as restart (clean state)
                    if($app_current_stats['status']['id'] > 0) {
                        
                        // Needs to be stopped first.
                        $tasks = new Tasks();
                        $tasks->task = 'stop_edgeapp';
                        $tasks->args = json_encode(['id' => $app_current_status['id']]);
                        $tasks->save();

                    }

                    // Edgeapp can be started now...
                    $tasks = new Tasks();
                    $tasks->task = 'start_edgeapp';
                    $tasks->args = json_encode(['id' => $app_current_status['id']]);
                    $tasks->save();

                    $action_result = 'executing';

                    break;
                
                case 'stop':
                    
                    // Needs to be stopped first.
                    $tasks = new Tasks();
                    $tasks->task = 'stop_edgeapp';
                    $tasks->args = json_encode(['id' => $app_current_status['id']]);
                    $tasks->save();

                    $action_result = 'executing';
                    
                    break;
                default:

                    $action_result = 'invalid_action';

                    break;
            }

        } else {
            $action_result = 'edgeapp_not_found';
        }

        $this->f3->get('twig')->display(
            'ApplicationsAction.twig', 
            [
                'framework_ready' => $framework_ready,
                'action' => $this->f3->get("PARAMS.action"),
                'edgeapp' => $this->f3->get('PARAMS.edgeapp'),
                'result' => $action_result,
            ]
        );

    }

    public function setup_access() {

        $options = new Options();
        $edgeboxio_api = new EdgeboxioApiConnector();
        
        $status = 'Waiting for Edgebox.io Account Credentials';
        $connection_status = 'Not connected';
        $connection_details = [];
        $task_status = 0;

        $is_post_request = !empty($this->f3->get('POST.username')) && !empty($this->f3->get('POST.password'));

        if($is_post_request) {

            // POST Request. Try to login to edgebox.io, obtain jwt token, save necessary info as options, issue setup of tunnel.

            // User submitted login credentials for API.
            $api_token = $edgeboxio_api->get_token($this->f3->get('POST.username'), $this->f3->get('POST.password'));
            if($api_token['status'] == 'success') {
                
                // Successfully got an jwt token. Save the token in database, for future requests.
                $options->load(array('name=?','EDGEBOXIO_API_TOKEN'));
                $options->name = 'EDGEBOXIO_API_TOKEN';
                $options->value = $api_token['value'];

                $options->save();

                // Request Edgebox.io API information about the bootnode.
                $tunnel_info = $edgeboxio_api->get_bootnode_info();

                if($tunnel_info['status'] == 'success') {

                    // The reponse was successful. Save fetched information in options and issue setup_tunnel task.

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

                    $options->load(array('name=?','NODE_NAME'));
                    $options->name = 'NODE_NAME';
                    $options->value = $tunnel_info['value']['node_name'];
                    $options->save();

                    // Issue tasks for SysCtl to setup the tunnel connection to myedge.app service.
                    $tasks = new Tasks();
                    $tasks->task = 'setup_tunnel';
                    $tasks->args = json_encode($tunnel_info['value']);
                    $tasks->save();

                    $connection_status = "Configuring tunnel network for " . $tunnel_info['value']['node_name'] . "...";
                    $connection_details = $tunnel_info['value'];

                } else {

                    // An error in the /bootnode API endpoint occured. Display error to user.

                    $status = json_encode($tunnel_info['value']);
                
                }

                
            } else {

                // An error occured with the login process (bad credentials, service unavailable, etc.) Display error to user.

                $status = $api_token['value'];
            
            }

        } else {

            // GET Request. Should get latest setup_tunnel task status and display it.

            $options->load(array('name=?','EDGEBOXIO_API_TOKEN'));
            $api_token = $options->value;  
            $show_form  = true;

            if(!empty($api_token)) {
            
                // We have an API token, which means that a previous login and tunnel setup was made.
                // We can check the task status.

                $show_form = false;

                // Is already logged in, and not doing this request through post

                $tunnel_info = $edgeboxio_api->get_bootnode_info($api_token);
                $connection_details = $tunnel_info['value'];

                $status = "Logged in to Edgebox.io as " . $connection_details['node_name'];
                $tunnel_setup_task = new Tasks();
                $tunnel_setup_task->load(array('task=?','setup_tunnel'));
                $task_status = $tunnel_setup_task->status;
                switch($task_status) {
                    case 0:

                        // Task has not yet been picked up by edgeboxctl...
                        $connection_status = "Waiting for Edgebox to start executing the setup...";
                        break;

                    case 1:

                        // Task has been picked up by edgeboxctl and is not in progress...
                        $connection_status = "Configuring tunnel network for " . $connection_details['node_name'] . "...";
                        // TODO: Some sort of auto-reload when the status is this one could be very useful.
                        break;

                    case 2:

                        // Task is complete and has result. In this, case the apps we will allow registration in the myedge.app service.
                        $connection_status = 'Successfully connected to myedge.app Service';
                        
                        break;

                    default:

                        // Error occurred and should be shown to the user.
                        $connection_status = json_decode($tunnel_setup_task->result)['value'];


                }

            
            }

        }

        $this->f3->get('twig')->display(
            'Access.twig', 
            [
                'show_form' => $show_form,
                'status' => $status,
                'connection_status' => $connection_status,
                'connection_details' => $connection_details,
                'task_status' => $task_status,
                'api_token' => $api_token,
            ]
        );

    }

    public function setup_access_logout() {

        $options = new Options();
        $options->load(array('name=?', 'EDGEBOXIO_API_TOKEN'));
        $options->name='EDGEBOXIO_API_TOKEN';
        $options->value = '';
        $options->save();

        // Issue tasks for SysCtl to setup the tunnel connection to myedge.app service.
        $tasks = new Tasks();
        $tasks->task = 'disable_tunnel';
        $tasks->args = json_encode([]);
        $tasks->save();

        $this->f3->reroute('/setup/access');

    }

}