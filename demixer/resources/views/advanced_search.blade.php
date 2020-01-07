<!-- Author: Matyáš Anton -->
@extends('layouts.app')

@section('content')
    <script>
        function redrawOutputFields() {
            var outputcount = parseInt(document.getElementById("outputcount").value);
            
            if (isNaN(outputcount) || outputcount < 1) {
                outputcount = 1;
                document.getElementById("outputcount").value = 1;
            }
            
            var outputtxfields = "";
            
            for (var i = 1; i <= outputcount; i++) {
                outputtxfields += '<div class="form-group row">'
                                + '<label for="transaction-id" class="col-sm-2 col-form-label">Output TX ' + i + '</label>'
                                + '<div class="col-sm-10">'
                                + '<input type="text" class="form-control" name="outputtx'+i+'" id="outputtx'+i+'" placeholder="TXID"></div></div>';
            }
            
            document.getElementById("outputtxs").innerHTML = outputtxfields;            
        }
        
        window.onload = redrawOutputFields;
    </script>
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Search for matching input/output transaction
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
                    @include('common.errors')

                    <form action="{{ url('searchrequest')}}" method="POST">
                        {{ csrf_field() }}
                    
                      <div class="form-group row">
                        <label for="transaction-id" class="col-sm-2 col-form-label">Input TX</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control" name="transaction_id" id="transaction-id" placeholder="TXID">
                        </div>
                      </div>  
                      
                    <div id="outputtxs">
                    </div>

                      <input type="hidden" name="searchtype" id="searchtype" value="all">
                      
                      <div class="form-group row">
                        <label for="outputcount" class="col-sm-2 col-form-label">No. of outputs</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control" name="outputcount" id="outputcount" value="1" onload="redrawOutputFields()" onchange="redrawOutputFields()">
                        </div>
                      </div>
                      
                      <div class="form-group row">
                        <label for="currency" class="col-md-2 col-form-label">Currency</label>
                        <div class="col-md-10">
                          <select id="currency" name="currency" class="form-control">
                            <option value="btc" selected>Bitcoin (BTC)</option>
                            <option value="ltc">Litecoin (LTC)</option>
                          </select>
                        </div>
                      </div>

                    <div class="form-group row">
                        <label for="mixer" class="col-md-2 col-form-label">Mixer</label>
                        <div class="col-md-10">
                          <select id="mixer" name="mixer" class="form-control">
                            <option value="bestmixer" selected>BestMixer.io</option>
                          </select>
                        </div>
                      </div>   
                      
                      <div class="form-group row">
                        <label for="cluster_db" class="col-md-2 col-form-label">Cluster. service</label>
                        <div class="col-md-10">
                          <select id="cluster_db" name="cluster_db" class="form-control">
                            <option value="tarzan" selected>Tarzan</option>
                            <option value="we">WalletExplorer (BTC only)</option>
                          </select>
                        </div>
                      </div>                      
                      
                      <div class="form-group row">
                        <label for="min_service_fee" class="col-md-2 col-form-label">Min. fee (%)</label>
                        <div class="col-md-4">
                          <input type="text" class="form-control" name="min_service_fee" id="min_fee" value="0">
                        </div>
                        <label for="max_service_fee" class="col-md-2 col-form-label">Max. fee (%)</label>
                        <div class="col-md-4">
                          <input type="text" class="form-control" name="max_service_fee" id="max_service_fee" value="4">
                        </div>                        
                      </div>
                      <div class="form-group row">
                        <label for="min_miner_fee" class="col-md-2 col-form-label">Min. address fee</label>
                        <div class="col-md-4">
                          <input type="text" class="form-control" name="min_miner_fee" id="min_fee" value="0">
                        </div>
                        <label for="max_miner_fee" class="col-md-2 col-form-label">Max. address fee</label>
                        <div class="col-md-4">
                          <input type="text" class="form-control" name="max_miner_fee" id="max_miner_fee" value="0.0004">
                        </div>                        
                      </div>                      
                      <div class="form-group row">
                        <label for="min_delay" class="col-md-2 col-form-label">Min. delay (hrs)</label>
                        <div class="col-md-4">
                          <input type="text" name="min_delay" class="form-control" id="min_delay" value="0">
                        </div>
                        <label for="max_delay" class="col-md-2 col-form-label">Max. delay (hrs)</label>
                        <div class="col-md-4">
                          <input type="text" name="max_delay" class="form-control" id="max_delay" value="72">
                        </div>                        
                      </div>                        
                      <div class="form-group row">
                        <div class="text-center">
                          <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                      </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
