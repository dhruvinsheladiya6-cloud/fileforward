@extends('vironeer::layouts.app')
@section('title', 'Licence Validation')
@section('content')
    <div class="vironeer-form-info mb-4">
        <p class="vironeer-form-info-title">{{ __('Licence Validation') }}</p>
        <p class="vironeer-form-info-text">
            {{ __('As part of to protect our products we build our system to validate a licence for every buyer, licence means your purchase code and you can follow the links below to learn more about it and how you can get it.') }}
        </p>
    </div>
    <div class="vironeer-requirements">
        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </div>
        @endif
        <div class="card mb-3">
            <div class="card-header bg-dark text-white"><i class="fa fa-key me-2"></i>{{ __('Your Licence') }}</div>
            <div class="card-body">
                <form action="{{ route('install.licence') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label"><strong>{{ __('Purchase Code') }}</strong> : <span
                                class="red">*</span></label>
                        <input type="text" name="purchase_code" class="form-control"
                            placeholder="Enter your purchase code" autocomplete="off" autofocus required>
                    </div>
                    <button class="btn btn-primary">{{ __('Validate') }}</button>
                </form>
            </div>
        </div>
        <div class="alert alert-info mb-4">
            <strong>{{ __('Note : ') }}</strong>
            {{ __('Please check the links below to learn more about licences.') }}
        </div>
        <div class="links">
            <p class="mb-2"><strong>{{ __('Quick Links :') }}</strong></p>
            <li class="mb-1"><a target="_blank"
                    href="https://codecanyon.net/licenses/standard">{{ __('What The Licence Mean ?') }}</a>
            </li>
            <li class="mb-1"><a target="__blank"
                    href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-">{{ __('Where Is My Purchase Code?') }}</a>
            </li>
            <li class="mb-1"><a target="_blank"
                    href="https://codecanyon.net/user/vironeer/portfolio">{{ __('Where I Can Bought a Licence ?') }}</a>
            </li>
        </div>
    </div>
@endsection
