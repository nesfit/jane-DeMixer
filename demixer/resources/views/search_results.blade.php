<!-- Author: Matyáš Anton -->
@extends('layouts.app')

@section('content')

    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Search finished
                </div>
                
                <div class="panel-body">
                    The results can be found below. The addresses in <span class="text-danger"><strong>red</strong></span> correspond to the entry and leaving points, i.e. where the money was transferrd to the cluster (in the input transaction) and where it was sent out back to the user's address (in output transactions).

                </div>
            </div>
        </div>
    </div>   
    
    <h3 class="container">Transaction(s) entered by user</h3>
    @foreach($results['inputs'] as $tx)
        @include('transaction', ['txtitle' => 'Input transaction', 'tx' => $tx])
    @endforeach
    
    @foreach($results['outputs'] as $key => $tx)
        @include('transaction', ['txtitle' => 'Output transaction #'. $key, 'tx' => $tx])
    @endforeach
    
    <h3 class="container">Possible matches ({{$results['result_count']}})</h3>
    
    @if($results['searchtype'] == 'out')
        @foreach($results['results'] as $tx)
            @include('transaction', ['txtitle' => 'Input transaction', 'tx' => $tx])
        @endforeach
    @elseif($results['searchtype'] == 'in')
        @foreach($results['results'] as $key => $reslist)
            @if(!empty($reslist))
                <h4 class="container">Matches for {{$key + 1}} address{{$key > 0 ? "es" : ""}} ({{count($reslist)}})</h4>
                @foreach ($reslist as $txlist)
                    <div class="container-fluid">
                    <div class="col-sm-12">
                        <div class="panel panel-default col-sm-12">
                             @foreach($txlist as $key => $tx)
                                @include('transaction', ['txtitle' => 'Output transaction #'. $key, 'tx' => $tx])
                             @endforeach   
                        </div>
                    </div>
                </div> 
                @endforeach
            @endif
        @endforeach
    @elseif($results['searchtype'] == 'all')
        @foreach($results['results'] as $reslist)
            <div class="container-fluid">
            <div class="panel panel-default col-sm-12">
            @if ($reslist['input'] != NULL)
                @include('transaction', ['txtitle' => 'Input transaction', 'tx' => $reslist['input']])
            @endif
            @foreach($reslist['outputs'] as $key => $reslist)
                @if(!empty($reslist))
                    <h4 class="container">Matches for {{$key + 1}} more output address{{$key > 0 ? "es" : ""}} ({{count($reslist)}})</h4>
                    @foreach ($reslist as $txlist)
                    <div class="container-fluid">
                        <div class="col-sm-12">
                            <div class="panel panel-default col-sm-12">
                                 @foreach($txlist as $key => $tx)
                                    @include('transaction', ['txtitle' => 'Output transaction '. $key, 'tx' => $tx])
                                 @endforeach   
                            </div>
                        </div>
                    </div> 
                    @endforeach
                @endif
            @endforeach
            </div>
            </div>
        @endforeach
    @endif
      
@endsection
