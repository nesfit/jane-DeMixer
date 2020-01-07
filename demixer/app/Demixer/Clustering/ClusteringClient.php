<?php
/**
 * File: BestMixerDemixer.php
 * Author: Matyáš Anton (xanton03@stud.fit.vutbr.cz)
 * Description: Abstract class for clustering clients
 */
namespace App\Demixer\Clustering;


abstract class ClusteringClient
{
    abstract public function getClusterAddresses($address);

    abstract public function getClusterTxs($address);
    abstract public function getClusterInTxs($address);
    abstract public function getClusterOutTxs($address);
}
