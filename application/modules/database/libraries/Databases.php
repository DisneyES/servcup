<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Databases {

    private $data;
    private $update;
    private $customer_id;

    function __construct($data)
    {
        $this->data = $data;
        $this->_CI = & get_instance();
        $this->_CI->load->model('DatabaseModel');
        //$this->_CI->lang->load("module");
        $this->customer_id = $this->_CI->session->userdata('customer_id');
    }

    /**
	 * list_databases
	 * get full result of database with users
	 *
	 * @access  public
     *
     * @return  html
	 */
    public function list_databases()
    {
        if(has_access(array('manage_database')))
		{
			$this->data['site'] = 'database';
			$this->data['title'] = lang('Manage databases');
			$this->data['jsFiles'] = array('database.js');
            $this->data['users'] = $this->_CI->DatabaseModel->listing_user();

			render_page(role().'/database', $this->data, TRUE);
		} else {
            no_access();
        }
    }

    /**
	 * get_databases
	 * get result of databases
	 *
	 * @access  public
     *
     * @return  html
	 */
    public function get_databases()
    {
        if (has_access(array('manage_database'))) {
            $users['total'] = $this->_CI->DatabaseModel->get_databases(TRUE);
            $users['rows'] = $this->_CI->DatabaseModel->get_databases();

            return send_output($users);
        } else {
            return send_output(array('status' => 500));
        }
    }

    /**
	 * get_database
	 * get database data
	 *
	 * @access  public
     *
     * @return  json
	 */
    public function get_database()
    {
        if (has_access(array('manage_database')) && $this->_CI->DatabaseModel->check_owner('db_name', trim($this->_CI->input->post('db_name')), 'sql_databases')) {

            $data = $this->_CI->DatabaseModel->get_database(trim($this->_CI->input->post('db_id')));

            return send_output($data);
        } else {
            return send_output(array('status' => 500));
        }
    }

    /**
	 * save_database
	 * create or update database
	 *
	 * @access  public
     * @param   $_POST['dbname']    Name of the database
     * @param   $_POST['username']  username for the database
     *
     * @return  html
	 */
    public function save_database()
    {
        if (has_access(array('manage_database'))) {

            $update = FALSE;
            if($this->_CI->input->post('db_id') != "")
            {
                $update = TRUE;
            }
            $remote = ($this->_CI->input->post('remote') == 1) ? '%' : 'localhost';

            $data = array(
                'server_id' => get_server('mysql')->id,
                'customer_id' => $this->customer_id,
                'db_name' => trim($this->_CI->input->post('dbname').'_'.$this->customer_id),
                'db_user' => $this->_CI->input->post('username'),
                'db_type' => 'MySQL',
                'remote' => $remote,
            );

            if(!$update) {
                if(!preg_match('/^([a-zA-Z0-9_])+$/i', trim($this->_CI->input->post('dbname')))){
                    return send_output(array('dbname' => lang('username character'), 'status' => 501));
                }
                else if($this->_CI->input->post('username') == "") {
                    return send_output(array('username' => lang('No user exist'), 'status' => 501));
                }
                else if(!$this->_CI->DatabaseModel->check_exist_db($data['db_name'])){
                    return send_output(array('dbname' => lang('db exist'), 'status' => 501));
                }
                else{
                    $this->_CI->DatabaseModel->create_database($data);
                    return send_output(array('status' => 200));
                }
            }else{


                if($this->_CI->DatabaseModel->check_owner('db_name', trim($this->_CI->input->post('db_name')), 'sql_databases'))
                {
                    unset($data['server_id']);
                    unset($data['customer_id']);
                    unset($data['db_type']);
                    unset($data['remote']);
                    unset($data['db_name']);

                    if($this->_CI->input->post('username') == "") {
                        return send_output(array('username' => lang('No user exist'), 'status' => 501));
                    }

                    $this->_CI->DatabaseModel->update_database(
                        trim($this->_CI->input->post('db_id')),
                        $this->_CI->input->post('username'),
                        trim($this->_CI->input->post('db_name')),
                        $data
                    );
                    return send_output(array('status' => 200));
                }
                else{
                    return send_output(array('status' => 500));
                }
            }
        } else {
            return send_output(array('status' => 500));
        }
    }

    /**
	 * delete_database
	 * delete database from db and server
	 *
	 * @access  public
     * @param   $_POST['db_id']    Id of the database
     * @param   $_POST['db_name']  Name for the database
     *
     * @return  html
	 */
    public function delete_database()
    {
        if (has_access(array('manage_database')) && $this->_CI->DatabaseModel->check_owner('db_name', trim($this->_CI->input->post('db_name')), 'sql_databases')) {
            $this->_CI->DatabaseModel->delete_database(
                trim($this->_CI->input->post('db_id')),
                trim($this->_CI->input->post('db_name'))
            );
            return send_output(array('status' => 200));
        } else{
            return send_output(array('status' => 500));
        }
    }
}
