<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Operation extends CI_Controller {
    private $zk;
    private $ns;
    public function __construct(){
        parent::__construct();
        $s = $this->config->item('zk_servers');
        $this->zkclient->connect($s);
        $this->ns = '/'.trim($this->config->item('namespace'),'/');
        if ( !file_exists(DATA_DIR.$this->ns) ) {
            mkdir(DATA_DIR.$this->ns,DIR_READ_MODE);
        }
        if ( !$this->zkclient->exists($this->ns) ) {
            $this->zkclient->makePath($this->ns);
        }
    }
	public function index() {
	}
    public function get_file() {
        $path = $this->input->get('path');
        $res = $this->zkclient->get($path);
        $charset = $this->input->get('charset');
        if ( empty($charset) ) {
            $charset = mb_check_encoding($res,'GBK') ? 'GBK' : 'UTF-8';
        }
        $content = @iconv($charset, 'UTF-8//IGNORE', $res);
        if ( $content===false ) { $content=''; }
        echo json_encode(array('charset'=>$charset, 'content'=>$content));
    }
    public function save_file() {
        $path = $this->input->post('path');
        $charset = $this->input->post('charset');
        $content = iconv('UTF-8', $charset, $this->input->post('content'));
        $zk_res = $this->zkclient->set($path, $content);
        if ( !is_dir(dirname(DATA_DIR.$path)) ) {
            mkdir(dirname(DATA_DIR.$path), DIR_READ_MODE, true);
        }
        $disk_res = file_put_contents(DATA_DIR.$path, $content);
        if ( $zk_res&&$disk_res ) {
            echo 'Y';
        }
    }
    public function make_path() {
        $path = $this->input->get('path');
        $zk_res = $this->zkclient->makePath($path);
        $disk_res = mkdir(DATA_DIR.$path, DIR_READ_MODE, true);
        if ( $zk_res&&$disk_res ) {
            echo 'Y';
        }
    }
	public function show() {
        $id = $this->input->post('id');
        if ( empty($id) ) {
            echo json_encode(array(array('id'=>$this->ns, 'text'=>trim($this->ns,'/'), 'state'=>'closed')));
            exit;
        }
        $path = $id;
        $nodes = $this->zkclient->getChildren($path);
        $json = array();
        foreach($nodes as $v) {
            $r = array(
                'id'=>$path.'/'.$v,
                'text'=>$v,
                'state' => is_dir(DATA_DIR.$path.'/'.$v) ? 'closed' : 'open',
            );
            $json []= $r;
        }
        echo json_encode($json);
	}
    public function del_file() {
        $path = $this->input->get('path');
        $zk_res = $this->zkclient->delete($path);
        $file_res = unlink(DATA_DIR.$path);
        if ( $zk_res&&$file_res ) {
            echo 'Y';
        }
    }
    public function del_folder() {
        $path = $this->input->get('path');
        $zk_res = $this->zkclient->delete($path);
        function rrmdir($dir) {
            foreach(glob($dir . '/*') as $file) {
               if(is_dir($file))
                   rrmdir($file);
               else
                   unlink($file);
            }
            rmdir($dir);
            return true;
        }
        $disk_res = rrmdir(DATA_DIR.$path);
        if ( $zk_res&&$disk_res ) {
            echo 'Y';
        }
    }
}
