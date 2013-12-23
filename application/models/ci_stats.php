<?php

/**
 * ci_stats short summary.
 *
 * ci_stats description.
 *
 * @version 1.0
 * @author Fusspawn
 */
class Ci_stats extends CI_Model
{
    var $typeid;
    var $buyprice;
    var $sellprice;
    var $buyvolume;
    var $sellvolume;
    var $recordedat;
    var $systemid;
    
    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->spark("curl/1.3.0");
    }
    
    function test_curl() {
        echo $this->get_stats(43, 30000142);
    }
    
    function update_stats_single($typeid, $systemid) {
        $this->typeid = $typeid;
        $this->systemid = $systemid;
        
        $xmlstring = $this->curl->simple_get("http://api.eve-central.com/api/marketstat?typeid=$typeid&usesystem=$systemid");
        $xml= new SimpleXMLElement($xmlstring);
        
        $item = $xml->xpath('/evec_api/marketstat/type[@id='.$typeid.']');
        if(!isset($item))
            return;
        
        $this->sellprice = (float) $item[0]->sell->avg;
        $this->buyprice = (float) $item[0]->buy->avg;
        $this->buyvolume = (int) $item[0]->buy->volume / 7;
        $this->sellvolume = (int) $item[0]->sell->volume / 7;
        $this->recordedat = time();
        
        $this->db->insert("pricedata", $this);
    }
    
    function update_price_array($types, $systemid) {
        $url = "http://api.eve-central.com/api/marketstat?&usesystem=$systemid";
        foreach($types as $type)
            $url = $url . "&typeid=$type";
        
        $xmlstring = $this->curl->simple_get($url);
        
        try {
            $xml = new SimpleXMLElement($xmlstring);
        } catch (Exception $e) {
            echo "Cant Parse: Content Was: $xmlstring";
        }
        
        $to_store = array();
        
        foreach($types as $type) {
            
            $item = $xml->xpath('/evec_api/marketstat/type[@id='.$type.']');
            
            $subitem = array(
                "typeid" => $type,
                "systemid" => $systemid,
                "sellprice" => (float) $item[0]->sell->min,
                "buyprice" => (float) $item[0]->buy->max,
                "buyvolume" => (float) $item[0]->buy->volume,
                "sellvolume" => (float) $item[0]->sell->volume,
                "recordedat" => time()
            );
                    
            
            array_push($to_store, $subitem);
        }
        
        $this->db->insert_batch("pricedata", $to_store);
    }
    
    function clean_up_data() {
        $this->db->where("buyprice", 0);
        $this->db->where("sellprice", 0);
        $this->db->where("buyvolume", 0);
        $this->db->where("sellvolume", 0);
        $query = $this->db->get("pricedata");
        $removeids = array();
        
        foreach($query->result() as $item) {
            array_push($removeids, $item->typeid);
        }
        
        $this->db->where_in("id", $removeids);
        $this->db->update("type_ids", array("onmarkets" => false));
        
        $this->db->where("buyprice", 0);
        $this->db->where("sellprice", 0);
        $this->db->where("buyvolume", 0);
        $this->db->where("sellvolume", 0);
        $query = $this->db->delete("pricedata");
    }
    
    function get_stats($typeid, $systemid) 
    {
        $this->db->order_by("recordedat", "desc");
        
        $query = $this->db->get_where("pricedata", array("typeid"=>$typeid, 
            "systemid"=>$systemid), 1, 0);
        
        foreach($query->result() as $row) 
        {
            if(time() - (60 * 60 * 5) < $row->recordedat) {
                $this->update_stats_single($typeid, $systemid);
                return $this->get_stats($typeid, $systemid);
            }
            
            return $row;
        }
        
        $this->update_stats_single($typeid, $systemid);
        return $this->get_stats($typeid, $systemid);
    }
    
    
    function lowest_sell($typeid) {
        $this->db->where("typeid", $typeid);
        $this->db->order_by("sellprice", "asc");
        $this->db->limit(1);
        
        $query = $this->db->get();
        foreach($query->result() as $row) {
            return $row;
        }
    }
    
    function highest_buy($typeid) {
        $this->db->where("typeid", $typeid);
        $this->db->order_by("buyprice", "desc");
        $this->db->limit(1);
        
        $query = $this->db->get();
        foreach($query->result() as $row) {
            return $row;
        }
    }
}
