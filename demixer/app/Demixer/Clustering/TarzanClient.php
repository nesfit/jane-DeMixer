<?php
/**
 * File: TarzanClient.php
 * Author: Matyáš Anton (xanton03@stud.fit.vutbr.cz)
 * Description: Clustering client that uses internal school server
 */
namespace App\Demixer\Clustering;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Demixer\BlockchainClient;
use GuzzleHttp\Client;

class TarzanClient extends ClusteringClient
{
    private $currency;
    
    public function __construct($currency = 'btc') {
        $this->currency = $currency;
    }

    public function getRequestURL($address, $request) {
        $cluster_client = config('demix.cluster_client');
        //dd("$cluster_client/$this->currency/mix/$address/$request");
        return "$cluster_client/$this->currency/mix/$address/$request";
    }

    public function getClusterInfo($address) {
        $addrlist = $this->getClusterAddresses($address);
        $txinlist = $this->getClusterInTxs($address);
        $txoutlist = $this->getClusterOutTxs($address);

        return new ClusterInfo ($addrlist, $txinlist, $txoutlist);
    }

    private function sendRequest($address, $request) {
        $url = $this->getRequestURL($address, $request);
        $client = new Client();

        try {
            $response = $client->get($url);
            return json_decode($response->getBody());
            }
        catch (\Exception $e) {
            return array();
        }
    }
    
    public function getClusterAddresses($address) {
        return $this->sendRequest($address, 'addrs');
    }

    public function getClusterTxs($address) {
        return $this->sendRequest($address, 'txs');
        
    }
    
    public function getClusterInTxs($address){
        return $this->sendRequest($address, 'txs?out');;
    }
    
    public function getClusterOutTxs($address){
        return $this->sendRequest($address, 'txs?in');;
    }
}
