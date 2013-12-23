<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use Pheal\Pheal;
use Pheal\Core\Config;

class Tasks extends CI_Controller {
    
	public function index()
	{
        $this->load->helper("url");
        $this->load->view('header');
		$this->load->view('welcome_message');
        $this->load->view('footer');
	}
    
    public function update_type_ids() {
        set_time_limit ( 0 );
        $this->load->model('item_ids', "ids");
        $this->ids->rebuild();
    }
    
    
    public function show_logs() {
        $this->load->spark("fire_log/0.7.0");
    }
    
    public function clean_up_db() {
        set_time_limit(0);
        $this->load->model("ci_stats", "marketdb");
        $this->marketdb->clean_up_data();
    }
    
    
    public function update_prices() {
        set_time_limit(0);
        $this->load->model("ci_stats", "marketdb");
        $this->output->enable_profiler(TRUE);
        $this->db->truncate("pricedata");
        
        $this->db->where("importprices", true);
        $this->db->select('solarSystemID');
        $systems = $this->db->get("mapsolarsystems");
        $pollsystems = array();
        
        foreach($systems->result() as $system)
        {
            array_push($pollsystems, $system->solarSystemID);
        }
        
        
        $jita = 30000142;
        $dodixie = 30002659;
        $hek = 30002053;
        
        
        $this->db->where("onmarkets", true);
        $query = $this->db->get("type_ids");
        $array_section = array();
        $items = 0;
        
        foreach($query->result() as $row)
        { 
            
            array_push($array_section, $row->id); 
            $items++;
            
            log_message("debug", "sending chunk requests and persisting");
            
            if(count($array_section) > 75) {
                
                foreach($pollsystems as $sysid) {
                    $this->marketdb->update_price_array($array_section, $sysid);
                }
                
                $array_section = array();
            }
            
            log_message("debug", "price chunk updated");
        }
        
        if(count($array_section) != 0)
        {
            foreach($pollsystems as $sysid) {
                $this->marketdb->update_price_array($array_section, $sysid);
            }
            
            $array_section = array();
        }
        
        log_message("debug", "grabbed last batch");        
        log_message("debug", "updated $items items. across ". count($pollsystems) ." systems");
        log_message("debug", "cleaning up.");
        
        $this->clean_up_db();
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */