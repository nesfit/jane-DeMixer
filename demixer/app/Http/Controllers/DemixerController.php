<?php
/**
 * File: DemixerController.php
 * Author: Matyáš Anton (xanton03@stud.fit.vutbr.cz)
 * Description: Controller handling comunication with view and model
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Demixer\Clustering\WalletExplorerClient;
use App\Demixer\Clustering\TarzanClient;
use App\Demixer\BlockchainClient;
use App\Demixer\BestMixerDemixer;
use App\Demixer\BestMixerLTCDemixer;
use GuzzleHttp\Client;

class DemixerController extends Controller
{   

    public function findMatchingTransactions(Request $request) {

        /* Removing potential trailing spaces from input*/
        $txid = trim($request->input('transaction_id'));
        $currency = $request->input('currency');
                
        $bc_client = new BlockchainClient($currency);
        
        /* Determine & create cluster client */
        switch ($request->cluster_db) {
            case "we":
                $cluster_client = new WalletExplorerClient();
                break;
            case "tarzan":
            default:
                $cluster_client = new TarzanClient($currency);
                break;
        }

        /* Determine mixer */
        switch ($request->mixer) {
            case "bestmixer":
                if ($currency == 'btc') {
                    $demixer = new BestMixerDemixer($bc_client, $cluster_client);
                }
                else if ($currency == 'ltc') {
                    $demixer = new BestMixerLTCDemixer($bc_client, $cluster_client);
                }
            default:                
                break;
        }
        
        try {
            if ($request->searchtype == "in") {
                return view('search_results')->with('results', $demixer->getTransactionOutputs($txid))->with('currency',$currency);
            }
            else if ($request->searchtype == "out") {
                return view('search_results')->with('results', $demixer->getTransactionInput(array($txid)))->with('currency',$currency);
            }
            else if ($request->searchtype == "all") {
                $demixer->reinitialize(1, $request->input('outputcount'), $request->input('min_service_fee'), $request->input('max_service_fee'),
                    $request->input('min_miner_fee'),$request->input('max_miner_fee'), $request->input('min_delay'), $request->input('max_delay'));
                
                $outtxs = [];

                for ($i = 1; $i <= $request->input('outputcount'); $i++) {
                    $response = trim($request->input('outputtx' . $i));
                    if ($response != "") {
                        $outtxs[] = $response;
                    }
                }
                
                $demixed = $demixer->GetTransactionAll(($txid ? $txid : NULL), $outtxs, $request->input('outputcount'));
                return view('search_results')->with('results', $demixed)->with('currency',$currency);
                
            }
         } catch (\Exception $e) {
            return view('error')->with('error', $e->getMessage());
        }
}


    public function searchTransaction($id, $searchtype) {
        
        return redirect('/');
    }
}
