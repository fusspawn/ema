<?php
use Pheal\Pheal;
use Pheal\Core\Config;

/**
 * bookkeeping short summary.
 *
 * bookkeeping description.
 *
 * @version 1.0
 * @author Fusspawn
 */
class Finance extends CI_Model
{
    public function UpdateFinances() {
        $keyID = 2874055;
        $vCode = "OFh6BVLH40o4wgqsjN5D42xa3mSyzag2Vf0GOlP2dvK14EFbVFUSKUf4iT4hCYVP";
        $characterid = 92956700;
        
        Config::getInstance()->cache = new \Pheal\Cache\FileStorage('E:/tmp/phealcache/');
        Config::getInstance()->access = new \Pheal\Access\StaticCheck();
        
        $this->load->database();
        $this->db->truncate('corp_wallet_current');
        $pheal = new Pheal($keyID, $vCode, "corp");
        
        try {
            // parameters for the request, like a characterID can be added
            // by handing the method an array of those parameters as argument
            $response = $pheal->AccountBalance(array("characterID" => $characterid));
            $accounts_data = array(
                "balanceone" => $response->accounts[0]->balance,
                "balancetwo" => $response->accounts[1]->balance,
                "balancethree" => $response->accounts[2]->balance,
                "balancefour" => $response->accounts[3]->balance,
                "balancefive" => $response->accounts[4]->balance,
                "balancesix" => $response->accounts[5]->balance,
                "balanceseven" => $response->accounts[6]->balance,
            );
            
            $this->db->insert('corp_wallet_current', $accounts_data);
            $this->db->insert('corp_wallet_history', $accounts_data);
        }
        
        catch (\Pheal\Exceptions\PhealException $e) {
            echo sprintf(
                "an exception was caught! Type: %s Message: %s",
                get_class($e),
                $e->getMessage()
            );
        }
    }
    
    
    public function UpdateTransactions() {
        $keyID = 2874055;
        $vCode = "OFh6BVLH40o4wgqsjN5D42xa3mSyzag2Vf0GOlP2dvK14EFbVFUSKUf4iT4hCYVP";
        $characterid = 92956700;
        
        Config::getInstance()->cache = new \Pheal\Cache\FileStorage('E:/tmp/phealcache/');
        Config::getInstance()->access = new \Pheal\Access\StaticCheck();
        
        $this->load->database();
        $pheal = new Pheal($keyID, $vCode, "corp");
        
        $this->db->select("transactionID");
        $this->db->order_by("transactionID", "desc");
        $this->db->limit(1);
        
        
        
        
        try {
            // parameters for the request, like a characterID can be added
            // by handing the method an array of those parameters as argument
            $response = $pheal->WalletTransactions(array("characterID" => $characterid,
                "accountKey" => 1000));
            
            foreach($response->transactions as $transaction) {
                $insert_query = $this->db->insert_string('corp_transactions', $transaction);
                $insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO', $insert_query);
                $this->db->query($insert_query); 
            }
        }
        
        catch (\Pheal\Exceptions\PhealException $e) {
            echo sprintf(
                "an exception was caught! Type: %s Message: %s",
                get_class($e),
                $e->getMessage()
            );
        }
    }
    
    public function UpdateMarketOrders() {
        $keyID = 2874055;
        $vCode = "OFh6BVLH40o4wgqsjN5D42xa3mSyzag2Vf0GOlP2dvK14EFbVFUSKUf4iT4hCYVP";
        $characterid = 92956700;
        
        Config::getInstance()->cache = new \Pheal\Cache\FileStorage('E:/tmp/phealcache/');
        Config::getInstance()->access = new \Pheal\Access\StaticCheck();
        
        $this->load->database();
        $this->db->truncate('corp_market_orders');
        $pheal = new Pheal($keyID, $vCode, "corp");
        
        $this->db->select("transactionID");
        $this->db->order_by("transactionID", "desc");
        $this->db->limit(1);
        
        try {
            // parameters for the request, like a characterID can be added
            // by handing the method an array of those parameters as argument
            $response = $pheal->MarketOrders(array("characterID" => $characterid));
            foreach($response->orders as $order) {
                $insert_query = $this->db->insert('corp_market_orders', $order);
            }
        }
        
        catch (\Pheal\Exceptions\PhealException $e) {
            echo sprintf(
                "an exception was caught! Type: %s Message: %s",
                get_class($e),
                $e->getMessage()
            );
        }
        
        
        echo "Done updating orders";
    }
    
    
    public function GetAllTransactions() {
        $this->load->database();
        $query = $this->db->get("corp_transactions");
        return $query->result();
    }
    
    public function GetAllBalances() {
        $this->load->database();
        $query = $this->db->get("corp_wallet_current");
        return $query->result();
    }
}
