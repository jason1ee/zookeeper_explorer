<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class ZKClient extends Zookeeper {
    /**
     * Set a node to a value. If the node doesn't exist yet, it is created.
     * Existing values of the node are overwritten
     *
     * @param string $path  The path to the node
     * @param mixed  $value The new value for the node
     *
     * @return mixed previous value if set, or null
     */
    public function set($path, $value) {
        if (!parent::exists($path)) {
            $parent = substr($path,0,strrpos($path,'/'));
            if ( !parent::exists($parent) ) {
                $this->makePath($parent);
            }
            return $this->makeNode($path, $value);
        } else {
            return parent::set($path, $value);
        }
    }

    /**
     * Equivalent of "mkdir -p" on ZooKeeper
     *
     * @param string $path  The path to the node
     * @param string $value The value to assign to each new node along the path
     *
     * @return bool
     */
    public function makePath($path, $value = '') {
        $parts = explode('/', $path);
        $parts = array_filter($parts);
        $subpath = '';
        while (count($parts) >= 1) {
            $subpath .= '/' . array_shift($parts);
            if (!parent::exists($subpath)) {
                $res = $this->makeNode($subpath, $value);
                if ( $res!=$subpath ) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Create a node on ZooKeeper at the given path
     *
     * @param string $path   The path to the node
     * @param string $value  The value to assign to the new node
     * @param array  $params Optional parameters for the Zookeeper node.
     *                       By default, a public node is created
     *
     * @return string the path to the newly created node or null on failure
     */
    public function makeNode($path, $value, array $params = array()) {
        if (empty($params)) {
            $params = array(
                array(
                    'perms'  => Zookeeper::PERM_ALL,
                    'scheme' => 'world',
                    'id'     => 'anyone',
                )
            );
        }
        return parent::create($path, $value, $params);
    }

    /**
     * Get the value for the node
     *
     * @param string $path the path to the node
     *
     * @return string|null
     */
    public function get($path) {
        if (!parent::exists($path)) {
            return null;
        }
        return parent::get($path);
    }

    /**
     * List the children of the given path, i.e. the name of the directories
     * within the current node, if any
     *
     * @param string $path the path to the node
     *
     * @return array the subpaths within the given node
     */
    public function getChildren($path) {
        if (strlen($path) > 1 && preg_match('@/$@', $path)) {
            // remove trailing /
            $path = substr($path, 0, -1);
        }
        return parent::getChildren($path);
    }
    public function delete($path) {
        if (strlen($path) > 1 && preg_match('@/$@', $path)) {
            // remove trailing /
            $path = substr($path, 0, -1);
        }
        $file_set = array($path);
        while( $now = array_shift($file_set) ) {
            $children = $this->getChildren($now);
            if ( empty($children) ) {
                $r = parent::delete($now);
                if ( !$r ) { return false; }
                continue;
            }
            array_unshift($file_set,$now);
            foreach($children as $c) {
                array_unshift($file_set,$now.'/'.$c);
            }
        }
        return true;
    }
}
