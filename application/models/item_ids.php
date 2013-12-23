<?php


class Item_ids extends CI_Model
{   
    var $id;
    var $name;
    
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    function insert($id, $name)
    {
        $this->id = $id;
        $this->name = $name;        
        $this->db->insert("type_ids", $this);
    }
    
    function get_id($name) {
        $query = $this->db->get_where('type_ids', array('name' => $name), 1, 0);
        foreach ($query->result() as $row)  {
            return $row->id;
        }
        
        return -1;
    }
    
    function get_name($id) {
        $query = $this->db->get_where('type_ids', array('id' => $id), 1, 0);
        foreach ($query->result() as $row)  {
            return $row->name;
        }
        
        return "Invalid Item ID";
    }
    
    function rebuild() {
        echo "Dropping database". PHP_EOL;
        $this->db->empty_table('type_ids');
        echo "Done";
        $this->load->helper("file");
        
        $string = read_file('./application/models/typeid.txt');
        $lines = explode("\n", $string);
        $skipcount = 2;
        
        foreach($lines as $line) {
            $temp_id = (int)substr($line, 0, 12);
            $temp_name = substr($line, 12);
            $this->insert($temp_id, $temp_name);
            echo "inserted: $id :: $name" . PHP_EOL;
        }
        
        echo "type_ids rebuilt";
    }
}

?>