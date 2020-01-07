<!-- Author: Matyáš Anton -->
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="alert alert-danger">
            <strong>The requested search could not be completed.</strong>

            <br><br>
            <p>Reason: {{$error}}</p>
        </div>
    </div>
@endsection
