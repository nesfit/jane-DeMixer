<?php
/**
 * File: WalletExplorerClient.php
 * Author: Matyáš Anton (xanton03@stud.fit.vutbr.cz)
 * Description: Clustering client that uses WalletExplorer as its core
 */
namespace App\Demixer\Clustering;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Demixer\BlockchainClient;
use GuzzleHttp\Client;

class WalletExplorerClient extends ClusteringClient
{
    private $currency;
    private $client;
    
    public function __construct($currency = 'btc') {
        $this->currency = $currency;
        $this->client = new Client();
    }
        
    public function getClusterInfo($address) {
        try {
            $body = $this->getClusterResponse($address);
            $cluster_id = $this->getClusterID($body);
            
            $input_txs;
            $output_txs;
            $this->extractInputOutputTransactions($body, $input_txs, $output_txs);

            return new ClusterInfo($this->extractClusterAddresses($cluster_id), $input_txs, $output_txs);
        } catch (\Exception $e) {
            return NULL;
        }
    }
    
    public function getClusterAddresses($address) {
        $cluster_id = $this->getClusterID($this->getClusterResponse($address));
        return $this->extractClusterAddresses($cluster_id);
    }

    public function getClusterTxs($address) {
        $body = $this->getClusterResponse($address);
        $cluster_id = $this->getClusterID($body);
        
        $input_txs;
        $output_txs;
        $this->extractInputOutputTransactions($body, $input_txs, $output_txs);
        return array_merge($input_txs, $output_txs);
        
    }
    
    public function getClusterInTxs($address){
        $body = $this->getClusterResponse($address);
        $cluster_id = $this->getClusterID($body);
        
        $input_txs;
        $output_txs;
        $this->extractInputOutputTransactions($body, $input_txs, $output_txs);
        return $input_txs;
    }
    
    public function getClusterOutTxs($address){
        $body = $this->getClusterResponse($address);
        $cluster_id = $this->getClusterID($body);
        
        $input_txs;
        $output_txs;
        $this->extractInputOutputTransactions($body, $input_txs, $output_txs);
        return $output_txs;
    }

    
    private function getClusterID($body) {
        $matches;
        $valid = preg_match('/<a href="\/wallet\/([0-9a-fA-F]+)\/addresses"/', $body, $matches);
        if (!$valid) return; //TODO
        
        return $matches[1];
    }

    private function getClusterResponse($address){
        $url = $this->getServerURL();
        $response = $this->client->request('GET', $url . '?q=' . $address);
        return $response->getBody();
    }

    private function extractClusterAddresses($cluster_id) {
            $this->client = new Client();
            /* Getting list of addresses from the page */
            $page_num = 1;
            $addresses = array();
            
            do {
                $response = $this->client->request('GET', $this->getServerURL() . 'wallet/' . $cluster_id . '/addresses?page=' . $page_num);
                
                if (!isset($page_max)) {
                      $valid = preg_match('/Page ' . $page_num . ' \/ ([0-9]+)/', $response->getBody(), $matches);
                      $page_max = $matches[1];
                }

                $addresses_cnt = preg_match_all('/<a href=\"\/address\/([0-9a-zA-Z]+)\"/', $response->getBody(), $addr_matches);
                $addresses = array_merge($addresses, $addr_matches[1]);
            } while ($page_num++ < $page_max);
            
            return $addresses;
    }

    private function extractTransactionsFromPage($body, &$input_txs, &$output_txs){
        $matches;
        preg_match_all('/<tr class="received">.*\"\/txid\/([0-9a-fA-F]+)\"/', $body, $matches);
        $input_txs = $matches[1];
        
        preg_match_all('/<tr class="sent">.*\"\/txid\/([0-9a-fA-F]+)\"/', $body, $matches);
        $output_txs = $matches[1];
        
    }

    private function getServerURL() {
        return 'https://www.walletexplorer.com/';
    }

    private function extractInputOutputTransactions($body, &$input_txs, &$output_txs){
            $this->client = new Client();
            
            $matches;

            preg_match('/Page 1 \/ ([0-9]+)/', $body, $matches);
            $tx_page_max = $matches[1];
            
            
            $this->extractTransactionsFromPage($body, $input_txs, $output_txs);
            
            $tmp_in;
            $tmp_out;
            
            for ($page_num=2; $page_num <= $tx_page_max; $page_num++) {
                $response = $this->client->request('GET', $this->getServerURL() . 'wallet/' . $this->getClusterID($body) . '?page=' . $page_num);
                
                $this->extractTransactionsFromPage($response->getBody(), $tmp_in, $tmp_out);
                
                $input_txs = array_merge($input_txs, $tmp_in);
                $output_txs = array_merge($output_txs, $tmp_out);
            }
    }    
}
