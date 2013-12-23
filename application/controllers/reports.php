<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reports extends CI_Controller {	
	public function index()
	{
        $this->load->helper("url");
        $this->load->database();
        
        $this->db->select('solarSystemID');
        $this->db->select("solarSystemName");
        $this->db->where("importprices", true);
        $systems = $this->db->get("mapsolarsystems");
        
        $this->load->view('header');
        $this->load->view('navbar');
		$this->load->view('reports/create', array("systems" => $systems));
        $this->load->view('footer');
	}
    
    
    public function generate() {
        $fromsystem = $this->input->post("fromsystem");
        $tosystem = $this->input->post("tosystem");
        $aordertype = ($this->input->post("aordertype") == "buy" ? "buy" : "sell");
        $sordertype = ($this->input->post("sordertype") == "buy" ? "buy" : "sell");
        $minvol = $this->input->post("minvol");
        $minmargin = $this->input->post("minmargin");
        $minprofit = $this->input->post("minprofit");
        $this->load->helper("url");
        redirect("http://localhost/reports/advanced/$fromsystem/$aordertype/$tosystem/$sordertype/$minvol/$minmargin/$minprofit");  
    }
    
    function get_system_id($name) {
        $this->db->where("solarSystemName",$name);
        $query = $this->db->get("mapsolarsystems");
        foreach($query->result() as $row)
            return $row->solarSystemID;
    }
    
    public function advanced($fromsystem, $aordertype, $tosystem, $sordertype, $minvolumes=50, $minmargin=30, $minprofit=500000) {
        $this->load->database();
        $this->load->helper("url");
        
        $fsystemid = $this->get_system_id($fromsystem);
        $ssystemid = $this->get_system_id($tosystem);
            
        $this->db->where("systemid", $fsystemid);
        $query_purchase = $this->db->get("pricedata");
    
        $this->db->where("systemid", $ssystemid);
        $query_sale = $this->db->get("pricedata");
        
        $buyprices = array();
        $sellprices = array();
        $buyvolumes = array();
        $sellvolumes = array();
        
        
        foreach($query_purchase->result() as $purchase)
        {
            $use_buy_orders = $aordertype == "buy" ? true : false;
            
            if($use_buy_orders) {
                $buyprices[$purchase->typeid] = $purchase->buyprice;
                $buyvolumes[$purchase->typeid] = $purchase->buyvolume;
            }  else {
                $buyprices[$purchase->typeid] = $purchase->sellprice;
                $buyvolumes[$purchase->typeid] = $purchase->sellvolume;
            }
        }
        
        
        foreach($query_sale->result() as $purchase)
        {
            $use_buy_orders = $sordertype == "buy" ? true : false;
            
            if($use_buy_orders) {
                $sellprices[$purchase->typeid] = $purchase->buyprice;
                $sellvolumes[$purchase->typeid] = $purchase->buyvolume;
            }  else {
                $sellprices[$purchase->typeid] = $purchase->sellprice;
                $sellvolumes[$purchase->typeid] = $purchase->sellvolume;
            }
        }
        
        $this->db->where("onmarkets", true);
        $queryids = $this->db->get("type_ids");
        
        $profit_data = array();
        
        array_push($profit_data, array("Name", "Profit/Unit", "$fromsystem Price", 
            "$fromsystem Volume", "$tosystem Price", "$tosystem Volume", " Margin ", "Profit 100mn Invest"));
        
        
        foreach($queryids->result() as $row) {
            if($buyprices[$row->id] == 0 ||
                $sellprices[$row->id] == 0 ||
                $buyvolumes[$row->id] == 0 ||
                $sellvolumes[$row->id] == 0)
                continue;
            
            
            $profits = $sellprices[$row->id] - $buyprices[$row->id];
            $purchasefor100m = 100000000 / $sellprices[$row->id];
            $profits100m = $profits * $purchasefor100m;
            $buyvolume = $buyvolumes[$row->id];
            $sellvolume = $sellvolumes[$row->id];
            $name = $row->name;
            
            
            
            try {
                $onepercentbuyprice = (float) ($buyprices[$row->id] / 100);
                $profit_margin = $profits / $onepercentbuyprice;
            } catch(Exception $e) {
            }
            
            if  ($profits < $minprofit || 
                ($buyvolume < $minvolumes && $profit_margin < 20) || 
                ($sellvolume < $minvolumes && $profit_margin < 20)) {
                
                continue;
            }
            
            array_push($profit_data, array(
                $name,
                number_format($profits),
                number_format($buyprices[$row->id]),
                $buyvolume,
                number_format($sellprices[$row->id]),
                $sellvolume, 
                number_format($profit_margin),
                number_format($profits100m)));
        }
        
        $this->load->library("table");
        
        $tmpl = array (
                    'table_open'          => '<table border="0" id="reports_table" cellpadding="4" cellspacing="0" class="table table-striped table-bordered table-hover table-condensed">',
        );

        $this->table->set_template($tmpl);
        $tablestring = $this->table->generate($profit_data);
        
        $this->db->select('solarSystemID');
        $this->db->select("solarSystemName");
        $this->db->where("importprices", true);
        $systems = $this->db->get("mapsolarsystems");
        
        $this->load->view('header');
        $this->load->view('navbar');
        $this->load->view('reports/create', array("systems" => $systems));
        $this->load->view('reports/system_report', array(
            "system" => "From: $fromsystem To: $tosystem",
            "table" => $tablestring));
        
        $this->load->view("footer");
    }
    
    public function simple($systemname)
    {
        $this->load->database();
        $this->load->helper("url");
        
        $system_id = $this->get_system_id($systemname);
        
        $this->db->where("systemid", $system_id);
        $this->db->join("type_ids", "pricedata.typeid = type_ids.id");
        $query = $this->db->get("pricedata");
        
        
        $profit_data = array();
        array_push($profit_data, array("name", "profits", "buyvolume","sellvolume"));
        
        foreach($query->result() as $row)
        {
            $profits = $row->sellprice - $row->buyprice;
            $buyvolume = $row->buyvolume;
            $sellvolume = $row->sellvolume;
            $name = $row->name;
            
            array_push($profit_data, array(
                $name,
                $profits,
                $buyvolume,
                $sellvolume));
        }
        
        $this->load->library("table");
        $this->load->view("header");
        $this->load->view("navbar");
        $tablestring = $this->table->generate($profit_data);
        
        $this->load->view('reports/system_report', array(
            "system" => $systemname,
            "table" => $tablestring));
        
        $this->load->view("footer");
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */