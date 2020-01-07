<?php
/**
 * File: ClusterInfo.php
 * Author: Matyáš Anton (xanton03@stud.fit.vutbr.cz)
 * Description: Represents an object containing info regarding a cluster
 */

namespace App\Demixer\Clustering;

class ClusterInfo
{
    private $addresses;
    private $index_addresses;
    private $outputTransactions;
    private $inputTransactions;
    
    public function __construct($addresses, $inputtx, $outputtx) {
        $this->addresses = $addresses;
        $this->index_addresses = array_flip($addresses); // for faster checking
        $this->outputTransactions = $outputtx;
        $this->inputTransactions = $inputtx;
        
    }
    
    public function containsAddress($address) {
        return array_key_exists($address, $this->index_addresses);
    }
    
    public function getAddresses() {
        return $this->addresses;
    }
    
    public function getInputTransactions() {
        return $this->inputTransactions;
    }
    
    public function getInputTransactionCount() {
        return count($this->inputTransactions);
    }
    
    public function getOutputTransactionCount() {
        return count($this->outputTransactions);
    }
    
    public function getOutputTransactions() {
        return $this->outputTransactions;
    }
    
    public function getAllTransactions() {
        return array_merge($this->outputTransactions, $this->inputTransactions);
    }
    
    public function getTransactionCount() {
        return getInputTransactionCount() + getOutputTransactionCount();
    }
}
