<?php
/**
 * File: BestMixerDemixer.php
 * Author: Matyáš Anton (xanton03@stud.fit.vutbr.cz)
 * Description: Client used for getting blockchain info from BEXP
 */
namespace App\Demixer;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;

class BlockchainClient
{
    private $currency;
    
    public function getCurrency() {return $this.currency;}


    public function __construct($currency) {
        $this->currency = $currency;
    }
    
    public function getRequestURL($currency) {
        switch ($currency) {
            case 'btc': return 'http://bitcoin:local321@bexp.fit.vutbr.cz:10001';
            case 'ltc': return 'http://bitcoin:local321@bexp.fit.vutbr.cz:10002';
            default: return "";
        };
    }

    public function getRawTransactionJSON ($txid) {
        /* Get data about the user-inputted transaction */
        try {
            $url = $this->getRequestURL($this->currency);
            $client = new Client();
            $response = $client->post($url, [\GuzzleHttp\RequestOptions::JSON => [
                "jsonrpc" => "1.0",
                "id" => "curltext",
                "method" => "getrawtransaction", "params" => [$txid, 1]]
            ]);
            return $response->getBody();
        }
        catch (Exception $e) {
            return NULL;
        }
    }

    function getTXOutputs($txid) {
        $rawtx = $this->getRawTransactionJSON($txid);
        
        return $this->getTXOutputsFromJSON($rawtx);
    }
    
    function getTXOutputsFromJSON($rawtx) {
        $outputs = json_decode($rawtx, true)['result']['vout'];
        
        $result_outputs = array();
        foreach($outputs as $output) {
            $result_outputs[] = [
                                    "amount" => $output['value'],
                                    "address" => $output['scriptPubKey']['addresses'][0],
            ];
        }
        
        return $result_outputs;
    }
    
    function getTXInputsFromJSON($rawtx) {
        $inputs = json_decode($rawtx, true)['result']['vin'];
        
        $result_inputs = array();
        foreach($inputs as $input) {
            $inputinfo = $this->getTXOutputs($input['txid'])[$input['vout']];
            
            $result_outputs[] = [
                                    "amount" => $inputinfo['amount'],
                                    "address" => $inputinfo['address'],
            ];
        }
        
        return $result_outputs;
    }
    
    public function getTxInfo($txid) {
        $rawtx = $this->getRawTransactionJSON($txid);
        
        if ($rawtx == NULL) return NULL;
        
        $txdata = json_decode($rawtx, true)['result'];
        
        $inputs = $this->getTXInputsFromJSON($rawtx);
        $outputs = $this->getTXOutputsFromJSON($rawtx);

        return [
                 'time' => $txdata['time'], 
                 'txid' => $txdata['txid'], 
                 'inputs' => $inputs,
                 'outputs' => $outputs,
                ];
    }
}
