<?php
/**
 * File: BestMixerDemixer.php
 * Author: Matyáš Anton (xanton03@stud.fit.vutbr.cz)
 * Description: Class representing the demixing module for BestMixer
 */

namespace App\Demixer;

class BestMixerDemixer
{
    
    protected $bc_client;
    protected $cluster_client;
    protected $min_output_addrs;
    protected $max_output_addrs;
    protected $min_service_fee;
    protected $max_service_fee;
    protected $min_miner_fee;
    protected $max_miner_fee;
    protected $min_delay;
    protected $max_delay;
    
    /* Constructor with default initialization values */
    public function __construct ($bc_client, $clust_client, $min_output_addrs = 1, $max_output_addrs=10, $min_service_fee=1, $max_service_fee=4, $min_miner_fee=0,$max_miner_fee=0.0004, $min_delay=0, $max_delay=72) {
        $this->bc_client = $bc_client;
        $this->cluster_client = $clust_client;
        
        $this->min_output_addrs = $min_output_addrs;
        $this->max_output_addrs = $max_output_addrs;
        $this->min_service_fee = 0.01 * $min_service_fee;
        $this->max_service_fee = 0.01 * $max_service_fee;
        $this->min_miner_fee = $min_miner_fee;
        $this->max_miner_fee = $max_miner_fee;
        $this->min_delay = $min_delay*3600;
        $this->max_delay = $max_delay*3600;
    }
    
    /* Changes internal parameters */
    public function reinitialize($min_output_addrs, $max_output_addrs, $min_service_fee, $max_service_fee, $min_miner_fee, $max_miner_fee, $min_delay, $max_delay) {
        $this->min_output_addrs = $min_output_addrs;
        $this->max_output_addrs = $max_output_addrs;
        $this->min_service_fee = 0.01 * (float) $min_service_fee;
        $this->max_service_fee = 0.01 * (float) $max_service_fee;
        $this->min_miner_fee = (float) $min_miner_fee;
        $this->max_miner_fee = (float) $max_miner_fee;
        $this->min_delay = (int) $min_delay * 3600;
        $this->max_delay = (int) $max_delay * 3600;
    }
    
    
    protected function recursiveCompare($txlist, $cur_index, $target_amount, $cur_amount, $max_level, $cur_level) {
        $max_target_amount = (1-$this->min_service_fee) * $target_amount - $max_level * $this->min_miner_fee; 
        $min_target_amount = (1-$this->max_service_fee) * $target_amount - $max_level * $this->max_miner_fee;

        $results = array();
    
        for ($i = $cur_index; $i < count($txlist); $i++) {
            if ($cur_amount + $txlist[$i]['amount'] <= $max_target_amount) {
                if ($max_level > $cur_level) {
                    $local_results = $this->recursiveCompare($txlist, $i+1, $target_amount, $cur_amount + $txlist[$i]['amount'], $max_level, $cur_level+1);
                    
                    foreach ($local_results as $result) {
                       $results[] = array_merge(array($txlist[$i]), $result);
                    }
                    
                }
                else {
                    if (($cur_amount + $txlist[$i]['amount']) >= $min_target_amount
                    && ($cur_amount + $txlist[$i]['amount'] <= $max_target_amount)) {
                        $results[] = array($txlist[$i]);
                    }
                }
            }
        }
        return $results;
    }
    
    public function getTransactionOutputs($txid) {

        /* Get data about the user-entered transaction */
        $input_txinfo;
        try {
            $input_txinfo = $this->bc_client->getTxInfo($txid);
        }
        catch (\Exception $e) {
            throw new \Exception('Transaction not found.');
        }
        
        /* Finding the cluster address among the outputs */
        foreach ($input_txinfo['outputs'] as $output) {
            /* Check if possible to find address' cluster */
                $clust_info = $this->cluster_client->getClusterInfo($output['address']);
                if ($clust_info && $clust_info->getOutputTransactionCount() > 0 && $clust_info->getOutputTransactionCount() <= 2  && $clust_info->getInputTransactionCount() > 1) {
                    $input_tx = [
                                    'amount' => $output['amount'],
                                    'addr' => $output['address'],
                                    'full_info' => $input_txinfo,
                                ];
                }

            if (isset($input_tx)) break;
        }
        
        /* Check if cluster address could be determined */
        if (!isset($input_tx)) {
            throw new \Exception('None of the output addresses is part of a cluster');
        }
        
        //Storing cluster info locally to minimize requests
        $clust_info = $this->cluster_client->getClusterInfo($input_tx['addr']);

        $possible_tx_list = [];

        /* Creating list of transactions that have to be searched - for finding output */
        foreach ($clust_info->getInputTransactions() as $key => $tx) {
            $tx_info = $this->bc_client->getTxInfo($tx);
           
            /* Rough filtering - check if:
             * - 2 outputs, 1 input (output transaction format)
             * - the output money amount is less than input
             * - output time between input time + min/max delay */
            if (count($tx_info['inputs']) == 1 && count($tx_info['outputs']) == 2 && $clust_info->containsAddress($tx_info['outputs'][1]['address']) && $tx_info['outputs'][0]['amount'] < $input_tx['amount'] && $tx_info['time'] >= $input_tx['full_info']['time']+$this->min_delay && $tx_info['time'] <= $input_tx['full_info']['time']+$this->max_delay) {
                /* Add transaction data to the list */
                $possible_tx_list[] = [
                    'amount' => $tx_info['outputs'][0]['amount'],
                    'addr' => $tx_info['outputs'][0]['address'],
                    'full_info' => $tx_info,
                ];
            }
        }

        $resultcount = 0;
        
        /* Searching the transactions for matches (gradually for 1 to N output addresses) */
        for ($i = $this->min_output_addrs; $i <= $this->max_output_addrs; $i++) {
            $newresults = $this->recursiveCompare($possible_tx_list, 0, $input_tx['amount'], 0, $i, 1);
            $results[] = $newresults;
            $resultcount += count($newresults);
        }

        return array(
            'inputs' => array($input_tx),
            'outputs' => array(),
            'results' => $results,
            'result_count' => $resultcount,
            'searchtype' => 'in',
        );     
    }

    public function getTransactionInput($txid_outs) {
        $txout_list;

        /* Summary output transaction based on all known outputs */
        $output_tx = [
                        'amount' => 0,
                        'addr' => "",
                        'time_min' => 0,
                        'time_max' => 0,
                        'tx_count' => 0,
                        'full_info' => NULL,
                    ];
        
        foreach($txid_outs as $txid)  {
            /* Get data about the user-inputted transaction */
            $output_txinfo;
            try {
                $output_txinfo = $this->bc_client->getTxInfo($txid);
            }
            catch (\Exception $e) {
                //handling - possible duplicate
                throw new \Exception('Transaction not found.');
                return;
            }
            
            $output_tx_loc = [
                            'amount' => $output_txinfo['outputs'][0]['amount'],
                            'addr' => $output_txinfo['outputs'][0]['address'],
                            'full_info' => $output_txinfo,
                        ];


            if (!isset($output_txinfo['outputs'][1])) {
                throw new \Exception('An output transaction does not correspond to the selected mixing service.');
                return;
            }
            
            //Storing cluster info locally to minimize requests
            if (!isset($clust_info)) {
                $clust_info = $this->cluster_client->getClusterInfo($output_txinfo['outputs'][1]['address']);
            }
            
            if (!$clust_info->containsAddress($output_txinfo['outputs'][1]['address'])) {
                throw new \Exception('Cluster mismatch: Transactions not from the same cluster.');
                return;
            }
            
            /* Check if the TX is an output TX of a cluster */
            if (!((count($output_txinfo['outputs']) == 2 && count($output_txinfo['inputs']) == 1 && $clust_info->getInputTransactionCount() > 1 && $clust_info->getOutputTransactionCount() > 0 && $clust_info->getOutputTransactionCount() <= 2 ))) {
                throw new \Exception('An output transaction does not correspond to the selected mixing service.');
            }
            
            /* Updating summary tx */
            $output_tx['amount'] += $output_tx_loc['amount'];
            $output_tx['tx_count']++;
            if ($output_tx['time_min']) {
                $output_tx['time_min'] =  min($output_tx_loc['full_info']['time'], $output_tx['time_min']);
            }
            else {
                $output_tx['time_min'] =  $output_tx_loc['full_info']['time'];
            }
            if ($output_tx['time_max']) {
                $output_tx['time_max'] =  max($output_tx_loc['full_info']['time'], $output_tx['time_min']);
            }
            else {
                $output_tx['time_max'] =  $output_tx_loc['full_info']['time'];
            }            

            $txout_list[] = $output_tx_loc;
        }
        
        $possible_intx_list = [];


        $results = array();
        
        foreach ($clust_info->getInputTransactions() as $tx) {
            $tx_info = $this->bc_client->getTxInfo($tx);

            foreach($tx_info['outputs'] as $intx_output) {
                if ($clust_info->containsAddress($intx_output['address'])) {
                    
                    $txentry = [
                                    'amount' => $intx_output['amount'],
                                    'addr' => $intx_output['address'],
                                    'full_info' => $tx_info,
                               ];
                    
                    if ($output_tx['full_info']['txid'] != $tx_info['txid'] // check if not comparing with itself
                        && $txentry['amount'] * (1-$this->max_service_fee) - $this->max_miner_fee*$output_tx['tx_count'] <= $output_tx['amount'] // lower bounds
                        && $txentry['amount'] * (1-$this->min_service_fee) - $this->min_miner_fee*$output_tx['tx_count'] >= $output_tx['amount'] // upper bounds
                        && $tx_info['time'] >= $output_tx['time_max'] - $this->max_delay
                        && $tx_info['time'] <= $output_tx['time_min'] - $this->min_delay
                        ){
                            
                            $results[] = $txentry;
                    }
                    
                    break;
                }
            }
        }
        
        return array(
            'inputs' => array(),
            'outputs' => $txout_list,
            'results' => $results,
            'result_count' => count($results),
            'searchtype' => 'out',
        );
        
    }
    
    protected function pruneAddressesOverAmount($amount, $addresslist) {
        $prunedlist;
        foreach ($addresslist as $address) {
            if ($address['amount'] <= $amount) {
                $prunedlist[] = $address;
            }
        }
        return $prunedlist;
    }

    public function getTransactionAll($txid_in, $txid_outs, $out_count) {
        
        if ($txid_in == NULL && count($txid_outs) == $out_count) {
            return $this->getTransactionInput($txid_outs);
        }
        else if ($txid_in != NULL && empty($txid_outs)) {
            return $this->getTransactionOutputs($txid_in);
        }
        else if ($txid_in == NULL && empty($txid_outs)) {
            throw new \Exception('No transactions entered.');
        }

        /* Parsing output transactions */
        {
           $txout_list;

            /* Summary output transaction based on all known outputs */
            $output_tx = [
                            'amount' => 0,
                            'addr' => "",
                            'time_min' => 0,
                            'time_max' => 0,
                            'tx_count' => 0,
                            'full_info' => NULL,
                            'ids' => array(),
                        ];
            
            foreach($txid_outs as $txid)  {
                /* Get data about the user-inputted transaction */
                $output_txinfo;
                try {
                    $output_txinfo = $this->bc_client->getTxInfo($txid);
                }
                catch (\Exception $e) {
                    throw new \Exception('Transaction not found.');
                    return;
                }
                
                $output_tx_loc = [
                                'amount' => $output_txinfo['outputs'][0]['amount'],
                                'addr' => $output_txinfo['outputs'][0]['address'],
                                'full_info' => $output_txinfo,
                            ];


                if (!isset($output_txinfo['outputs'][1])) {
                    throw new \Exception('An output transaction does not correspond to the selected mixing service.');
                }

                //Storing cluster info locally to minimize requests
                if (!isset($clust_info)) {
                    $clust_info = $this->cluster_client->getClusterInfo($output_txinfo['outputs'][1]['address']);
                }
                
                if (!$clust_info->containsAddress($output_txinfo['outputs'][1]['address'])) {
                    throw new \Exception('Cluster mismatch: Transactions not from the same cluster.');
                }                
                
                /* Check if the TX is an output TX of a cluster */
                if (!((count($output_txinfo['outputs']) == 2 && count($output_txinfo['inputs']) == 1 && $clust_info->getInputTransactionCount() > 1 && $clust_info->getOutputTransactionCount() > 0 && $clust_info->getOutputTransactionCount() <= 2 ))) {
                    throw new \Exception('An output transaction does not correspond to the selected mixing service.');
                }
                
                /* Updating summary tx */
                $output_tx['amount'] += $output_tx_loc['amount'];
                $output_tx['tx_count']++;
                if ($output_tx['time_min']) {
                    $output_tx['time_min'] =  min($output_tx_loc['full_info']['time'], $output_tx['time_min']);
                }
                else {
                    $output_tx['time_min'] =  $output_tx_loc['full_info']['time'];
                }
                if ($output_tx['time_max']) {
                    $output_tx['time_max'] =  max($output_tx_loc['full_info']['time'], $output_tx['time_min']);
                }
                else {
                    $output_tx['time_max'] =  $output_tx_loc['full_info']['time'];
                } 
                $output_tx['ids'][] = $output_tx_loc['full_info']['txid'];

                $txout_list[] = $output_tx_loc;
            }
        }

        /* Parsing input transaction */
        if ($txid_in != NULL){
            /* Get data about the user-entered transaction */
            $input_txinfo;
            try {
                $input_txinfo = $this->bc_client->getTxInfo($txid_in);
            }
            catch (\Exception $e) {
                throw new \Exception('Transaction not found.');
            }

            /* Finding the cluster address among the outputs */
            foreach ($input_txinfo['outputs'] as $output) {
                /* Check if possible to find address' cluster */
                    $found = $this->cluster_client->getClusterInfo($output['address']);
                    if ($clust_info->containsAddress($output['address']) && $clust_info->getOutputTransactionCount() >= 0 && $clust_info->getOutputTransactionCount() <= 2 && $clust_info->getInputTransactionCount() > 1) {
                        $input_tx = [
                                        'amount' => $output['amount'],
                                        'addr' => $output['address'],
                                        'full_info' => $input_txinfo,
                                    ];
                    }
                if (isset($found)) break;
            }
            
            /* Check if cluster address could be determined */
            if (!isset($found)) {
                throw new \Exception('The input transaction does not belong to the same cluster as inputs.');
            }
        }
        $possible_in_list = [];
        $possible_out_list = [];
     
        if ($txid_in != NULL) {
            $possible_in_list = array($input_tx);
        }
        
        foreach ($clust_info->getInputTransactions() as $key => $tx) {
            if (in_array($tx, $txid_outs)) {continue;}
            
            $tx_info = $this->bc_client->getTxInfo($tx);
            
            /* Rough filtering - check if:
             * - 2 outputs, 1 input (output transaction format)
             * - the output money amount is less than input
             * - output time between input time + min/max delay */
            if (count($tx_info['inputs']) == 1 && count($tx_info['outputs']) == 2 && $clust_info->containsAddress($tx_info['outputs'][1]['address']) 
                && $tx_info['time'] >= $output_tx['time_max']-$this->max_delay && $tx_info['time'] <= $output_tx['time_min']+$this->max_delay) {
                /* Add transaction data to the list */
                $possible_out_list[] = [
                    'amount' => $tx_info['outputs'][0]['amount'],
                    'addr' => $tx_info['outputs'][0]['address'],
                    'full_info' => $tx_info,
                ];
            }
            if ($txid_in == NULL){
                foreach($tx_info['outputs'] as $intx_output) {
                    if ($clust_info->containsAddress($intx_output['address'])) {
                        
                        $txentry = [
                                        'amount' => $intx_output['amount'],
                                        'addr' => $intx_output['address'],
                                        'full_info' => $tx_info,
                                   ];
                        
                        if ($output_tx['full_info']['txid'] != $tx_info['txid'] // check if not comparing with itself
                            && $txentry['amount'] * (1-$this->min_service_fee) - $this->min_miner_fee*$output_tx['tx_count'] >= $output_tx['amount'] // upper bounds
                            && $tx_info['time'] >= $output_tx['time_max'] - $this->max_delay
                            && $tx_info['time'] <= $output_tx['time_min'] - $this->min_delay
                            ){
                                
                            $possible_in_list[] = $txentry;
                        }
                        
                        break;
                    }
                }
            }
  
        }
        
   
        $results = [];

        $counter=0;
        foreach($possible_in_list as $inaddr) {
            
            $res = [];
            $donotskip = false;

            if ($output_tx['amount'] <= ($inaddr['amount'] * (1-$this->min_service_fee) - $output_tx['tx_count'] * $this->min_miner_fee)
                && $output_tx['amount'] >= ($inaddr['amount'] * (1-$this->max_service_fee) - $output_tx['tx_count'] * $this->max_miner_fee)
                && $inaddr['full_info']['time'] <= $output_tx['time_min'] - $this->min_delay
                && $inaddr['full_info']['time'] >= $output_tx['time_max'] - $this->max_delay

                ) {
                    $results[] = array('input' => ($txid_in == NULL ? $inaddr : NULL), 'outputs' => array(), 'count');

                    $counter++;
                }
            
            /* Searching the transactions for matches (gradually for 1 to N output addresses) */
            for ($i = $this->min_output_addrs + $output_tx['tx_count']; $i <= $this->max_output_addrs; $i++) {

                $local_res = $this->recursiveCompare($possible_out_list, 0, $inaddr['amount'], $output_tx['amount'], $i, $output_tx['tx_count']+1);
                if (!empty($local_res)) {
                    $donotskip = true;
                    $counter+= count($local_res);
                }
                $res[] = $local_res;
                
                $str = $i . ' of ' . $this->max_output_addrs. '<br>';

            }

            if ($donotskip) {
                $results[] = array('input' => ($txid_in == NULL ? $inaddr : NULL), 'outputs' => $res, 'count');
            }
            
        }
        
        return array(
            'inputs' => ($txid_in == NULL ? array() : $possible_in_list),
            'outputs' => $txout_list,
            'results' => $results,
            'result_count' => $counter,
            'searchtype' => 'all',
        );        
    }

}
