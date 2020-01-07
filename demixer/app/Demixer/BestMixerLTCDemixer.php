<?php
/**
 * File: BestMixerLTCDemixer.php
 * Author: Matyáš Anton (xanton03@stud.fit.vutbr.cz)
 * Description: Class representing the LTC demixing module for BestMixer
 */
namespace App\Demixer;

class BestMixerLTCDemixer extends BestMixerDemixer
{
    
        public function __construct ($bc_client, $clust_client, $min_output_addrs = 1, $max_output_addrs=10, $min_service_fee=1, $max_service_fee=12, $min_miner_fee=0,$max_miner_fee=0.015, $min_delay=0, $max_delay=72) {
            
            parent::__construct ($bc_client, $clust_client, $min_output_addrs, $max_output_addrs, $min_service_fee, $max_service_fee, $min_miner_fee,$max_miner_fee, $min_delay, $max_delay);
        }

    
}
