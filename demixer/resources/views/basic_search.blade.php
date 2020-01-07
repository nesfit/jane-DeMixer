<!-- Author: Matyáš Anton -->
@extends('layouts.app')

@section('content')
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
                        <label for="transaction-id" class="col-sm-2 col-form-label">Transaction ID</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control" name="transaction_id" id="transaction-id" placeholder="TXID">
                        </div>
                      </div>  
                    
                      <fieldset class="form-group">
                        <div class="row">
                          <label class="col-form-label col-sm-2 pt-0">Search type</label>
                          <div class="col-sm-10">
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="searchtype" id="searchtype" value="in" checked>
                                Use as Input Transaction
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="searchtype" id="searchtype" value="out">
                               Use as Output Transaction
                            </div>
                          </div>
                        </div>
                      </fieldset>

                      
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
