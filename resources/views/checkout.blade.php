@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Checkout') }}</div>
                    <div class="card-body">
                        <form action="{{route('pay')}}" method="POST" id="payment-form">
                            @csrf
                            <input type="hidden" name="payment_method" id="payment-method" value="">
                            <input type="hidden" name="order_id" value="{{$order->id}}">
                            <div class="col-md-8">
                                <div id="card-element"></div>
                                <button id="payment-button" class="btn btn-primary mt-4" type="button">
                                    Pay ${{ round($order->product->price / 100, 2) }}
                                </button>
                                @if(session('error'))
                                    <div class="alert alert-danger mt-4">
                                        {{session('error')}}
                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://js.stripe.com/v3/">
    </script>
    <script>
        var stripe = Stripe('{{config('services.stripe.publishable_key')}}');
        var elements = stripe.elements();
        var cardElement = elements.create('card', {
            hidePostalCode: true,
            style: {
                base: {
                    iconColor: '#AAA',
                    color: '#333',
                    lineHeight: '40px',
                    fontWeight: 300,
                    fontFamily: 'Helvetica Neue',
                    fontSize: '15px',
                    '::placeholder': {
                        color: '#AAA'
                    }
                }
            }
        });
        cardElement.mount('#card-element');

        $('#payment-button').on('click', function () {
            $('#payment-button').attr('disabled', true);

            stripe.confirmCardSetup(
                '{{ $paymentIntent->client_secret }}',
                {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: '{{ auth()->user()->name }}'
                        }
                    }
                }
            ).then(function (result) {
                if (result.error) {
                    $('#payment-button').attr('disabled', false);
                    console.log(result.error.message);
                } else {
                    $('#payment-method').val(result.setupIntent.payment_method);
                    $('#payment-form').submit();
                }
            });
        })
    </script>
@endsection
