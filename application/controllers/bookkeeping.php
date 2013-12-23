<?php

/**
 * bookkeeping short summary.
 *
 * bookkeeping description.
 *
 * @version 1.0
 * @author Fusspawn
 */
class Bookkeeping extends CI_Controller
{
    public function update() {
        $this->load->model("finance");
        $this->finance->UpdateFinances();
        $this->finance->UpdateTransactions();
        $this->finance->UpdateMarketOrders();
    }
    
    public function market_orders() {
        $this->load->model("finance");
        
        $this->load->database();
        $this->db->where("volRemaining >", 0);
        $query = $this->db->get("corp_market_orders");
        
        $this->load->library("table");
        $tmpl = array (
            'table_open' => '<table border="1px" id="reports_table" cellpadding="4" cellspacing="0" class="table table-striped table-bordered table-hover table-condensed">',
        );
        $this->table->set_template($tmpl);
        $tablestring = $this->table->generate($query);
        
        $this->load->view('header');
        $this->load->view('navbar');
        $this->load->view('reports/system_report', array(
            "system" => "Market Orders",
            "table" => $tablestring));
        
        $this->load->view("footer");
    }
    
    public function simple_finances() {
        $this->load->model("finance");
        
        $this->load->database();
        $query = $this->db->get("corp_wallet_current");
        $this->load->library("table");
        
        
        $tmpl = array (
            'table_open' => '<table border="1px" id="reports_table" cellpadding="4" cellspacing="0" class="table table-striped table-bordered table-hover table-condensed">',
        );

        $this->table->set_template($tmpl);
        $tablestring = $this->table->generate($query);
        
        $this->load->view('header');
        $this->load->view('navbar');
        $this->load->view('reports/system_report', array(
            "system" => "Simple Finances",
            "table" => $tablestring));
        
        $this->load->view("footer");
    }
    
    public function simple_product_report() {
        $this->load->model("finance");
        
        $transactions = $this->finance->GetAllTransactions();
        
        $item_sales = array();
        $item_purchases = array();
        $item_names = array();
        $profit_array = array();
        $on_market = array();
        
        $this->db->select("typeID, volRemaining, price");
        $this->db->where("volRemaining !=", 0);
        
        $market_orders = $this->db->get("corp_market_orders");
        
        
        foreach($market_orders->result() as $order) {
            $on_market[$order->typeID] = $order->volRemaining * $order->price;
        }
        
        array_push($profit_array, array(
            "name", "profit", "sold", "purchased", "isk on market", "profit after stocksale"));
        
        
        foreach($transactions as $transaction) 
        {
            switch($transaction->transactionType)
            {
                case "buy":
                    $cost = $transaction->price * $transaction->quantity;
                    array_key_exists ( $transaction->typeID, $item_purchases ) ? 
                        $item_purchases[$transaction->typeID] += $cost
                      : $item_purchases[$transaction->typeID] = $cost;
                    $item_names[$transaction->typeID] = $transaction->typeName;
                    break;
                    
                case "sell":
                    $cost = $transaction->price * $transaction->quantity;
                    array_key_exists ( $transaction->typeID, $item_sales ) ? 
                        $item_sales[$transaction->typeID] += $cost
                      : $item_sales[$transaction->typeID] = $cost;
                    $item_names[$transaction->typeID] = $transaction->typeName;
                    break;
            }
        }
        
        foreach($item_sales as $id => $spent) {
            array_push($profit_array, array(
                "name" => $item_names[$id],
                "profit" => number_format($spent - $item_purchases[$id]),
                "sold" => number_format($spent),
                "bought" => number_format($item_purchases[$id]),
                "on_market" => number_format(array_key_exists($id, $on_market) ? $on_market[$id] : 0),
                "after sale" => number_format(array_key_exists($id, $on_market) ? 
                                                            ($spent  - $item_purchases[$id]) + $on_market[$id] 
                                                            : $spent - $item_purchases[$id])
            ));
        }
        
        $this->load->library("table");
        
        $tmpl = array (
                    'table_open'          => '<table border="0" id="reports_table" cellpadding="4" cellspacing="0" class="table table-striped table-bordered table-hover table-condensed">',
        );

        $this->table->set_template($tmpl);
        $tablestring = $this->table->generate($profit_array);
        
        $this->load->view('header');
        $this->load->view('navbar');
        $this->load->view('reports/system_report', array(
            "system" => "Simple Product Stats",
            "table" => $tablestring));
        
        $this->load->view("footer");
        
    }  
}
