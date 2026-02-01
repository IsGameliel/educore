@extends('layouts.main')

@section('content')
    <div class="intro-section" id="error-section">
        <div class="slide-1" style="background-image: url('{{ asset('asset/images/hero_1.jpg') }}'); background-size: cover; background-position: center;" data-stellar-background-ratio="0.5">
            <div class="overlay" style="background-color: rgba(0,0,0,0.6); position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
            <div class="container position-relative" style="z-index: 2;">
                <div class="row align-items-center justify-content-center text-center" style="min-height: 70vh;">
                    <div class="col-lg-8 text-white" data-aos="fade-up" data-aos-delay="100">
                        <h1 class="display-4 mb-3">😔 Oops! Something went wrong</h1>
                        <p class="lead mb-4">We’re having trouble sending your email right now.
                            Our mail server may be temporarily unavailable or experiencing connectivity issues.</p>

                        <p class="mb-5">Please try again later or reach out to our support team if the issue persists.</p>

                        <a href="{{ url()->previous() }}" class="btn btn-primary btn-pill py-3 px-5 mr-2">Go Back</a>
                        <a href="{{ url('/') }}" class="btn btn-light btn-pill py-3 px-5">Return Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="site-section bg-light">
        <div class="container text-center">
            <h2 class="section-title mb-3" data-aos="fade-up">We’ll fix this soon</h2>
            <p data-aos="fade-up" data-aos-delay="100">
                Our technical team has been notified and is working to restore full functionality.
                Thank you for your patience and understanding.
            </p>
            <img src="{{ asset('asset/images/undraw_server_down.svg') }}" alt="Server Down" class="img-fluid mt-4" style="max-width: 400px;">
        </div>
    </div>
@endsection
