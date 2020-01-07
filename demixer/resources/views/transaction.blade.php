<!-- Author: Matyáš Anton -->
    <div class="container">
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                <strong>{{ $txtitle }}</strong> ({{$tx['full_info']['txid']}})
                </div>

                <div class="panel-body">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Inputs
                            </div>
                            <ul class="list-group">
                            @foreach($tx['full_info']['inputs'] as $input)
                              <li class="list-group-item">{{$input['address']}}&nbsp; ({{$input['amount']}} {{strtoupper($currency)}})</li>
                            @endforeach 
                            </ul> 
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Outputs
                            </div>
                            <ul class="list-group">
                            @foreach($tx['full_info']['outputs'] as $input)
                              <li class="list-group-item
                              <?php if ($input['address'] == $tx['addr']) echo 'text-danger'; ?>">{{$input['address']}}&nbsp; ({{$input['amount']}} {{strtoupper($currency)}})</li>
                            @endforeach 
                            </ul> 
                        </div>
                    </div>
                    </div>                    
                </div>
            </div>
        </div>
    </div> 