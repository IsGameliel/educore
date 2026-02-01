<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Muli:300,400,700,900" rel="stylesheet">
    <link rel="stylesheet" href="asset/fonts/icomoon/style.css">
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
    <link rel="stylesheet" href="asset/css/jquery-ui.css">
    <link rel="stylesheet" href="asset/css/owl.carousel.min.css">
    <link rel="stylesheet" href="asset/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="asset/css/jquery.fancybox.min.css">
    <link rel="stylesheet" href="asset/css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="asset/fonts/flaticon/font/flaticon.css">
    <link rel="stylesheet" href="asset/css/aos.css">
    <link rel="stylesheet" href="asset/css/style.css">
</head>
<body data-spy="scroll" data-target=".site-navbar-target" data-offset="300">

<div class="site-wrap">

    <div class="site-mobile-menu site-navbar-target">
        <div class="site-mobile-menu-header">
            <div class="site-mobile-menu-close mt-3">
                <span class="icon-close2 js-menu-toggle"></span>
            </div>
        </div>
        <!-- Hardcoded mobile menu -->
        <div class="d-block d-lg-none px-3 py-3">
            <ul class="list-unstyled mb-0">
                <li><a href="#home-section" class="nav-link">Home</a></li>
                <li><a href="#courses-section" class="nav-link">About</a></li>
                <li><a href="#programs-section" class="nav-link">Modules</a></li>
                <li><a href="#teachers-section" class="nav-link">Services</a></li>
                <li><a href="#teachers-section" class="nav-link">Features</a></li>
                <li><a href="#teachers-section" class="nav-link">Pricing</a></li>
                <li><a href="#teachers-section" class="nav-link">Contact</a></li>
                @guest
                    <li class="cta mt-2"><a href="{{ url('register') }}" class="nav-link"><span>Get started</span></a></li>
                @else
                    <li class="cta mt-2"><a href="{{ url('home') }}" class="nav-link"><span>Dashboard</span></a></li>
                @endguest
            </ul>
        </div>
        <div class="site-mobile-menu-body"></div>
    </div>

    <header class="site-navbar py-4 js-sticky-header site-navbar-target" role="banner">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <div class="site-logo mr-auto w-25">
                    <a href="index.html">
                        <img src="{{ asset('asset/images/educore.png') }}" style="width: 60%;" alt="">
                    </a>
                </div>
                <div class="mx-auto text-center">
                    <nav class="site-navigation position-relative text-right" role="navigation">
                        <ul class="site-menu main-menu  mx-auto d-none d-lg-block m-0 p-0">
                            <li><a href="#home-section" class="nav-link">Home</a></li>
                            <li><a href="#courses-section" class="nav-link">About</a></li>
                            <li><a href="#programs-section" class="nav-link">Modules</a></li>
                            <li><a href="#teachers-section" class="nav-link">Services</a></li>
                            <li><a href="#teachers-section" class="nav-link">Features</a></li>
                            <li><a href="#teachers-section" class="nav-link">Pricing</a></li>
                            <li><a href="#teachers-section" class="nav-link">Contact</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="ml-auto w-25">
                    <nav class="site-navigation position-relative text-right" role="navigation">
                        @guest
                            <ul class="site-menu main-menu  site-menu-dark mr-auto d-none d-lg-block m-0 p-0">
                                <li class="cta"><a href="{{ url('register') }}" class="nav-link"><span>Get started</span></a></li>
                            </ul>
                        @else
                            <ul class="site-menu main-menu  site-menu-dark mr-auto d-none d-lg-block m-0 p-0">
                                <li class="cta"><a href="{{ url('home') }}" class="nav-link"><span>Dashboard</span></a></li>
                            </ul>
                        @endguest
                    </nav>
                    <a href="#" class="d-inline-block d-lg-none site-menu-toggle js-menu-toggle text-black float-right"><span class="icon-menu h3"></span></a>
                </div>
            </div>
        </div>
    </header>

    @yield('content')

    <footer class="footer-section bg-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h3>About Educore</h3>
                    <p>Educore is a comprehensive school management system designed to streamline academic and administrative tasks. From managing student records to facilitating seamless communication and providing actionable insights, Educore empowers schools to deliver excellence in education effortlessly.</p>
                </div>
                <div class="col-md-3 ml-auto">
                    <h3>Links</h3>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#">Home</a></li>
                        <li><a href="#">Courses</a></li>
                        <li><a href="#">Programs</a></li>
                        <li><a href="#">Teachers</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h3>Subscribe</h3>
                    <p>Stay updated with the latest news, features, and updates from Educore. Join our community and never miss an important announcement!</p>
                    <form action="#" class="footer-subscribe">
                        <div class="d-flex mb-5">
                            <input type="text" class="form-control rounded-0" placeholder="Email">
                            <input type="submit" class="btn btn-primary rounded-0" value="Subscribe">
                        </div>
                    </form>
                </div>
            </div>
            <div class="row pt-5 mt-5 text-center">
                <div class="col-md-12">
                    <div class="border-top pt-5">
                        <p>
                            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                            Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved <i class="icon-heart" aria-hidden="true"></i> by <a href="#" target="_blank" >Checkmate</a>
                            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

</div> <!-- .site-wrap -->

<script src="asset/js/jquery-3.3.1.min.js"></script>
<script src="asset/js/jquery-migrate-3.0.1.min.js"></script>
<script src="asset/js/jquery-ui.js"></script>
<script src="asset/js/popper.min.js"></script>
<script src="asset/js/bootstrap.min.js"></script>
<script src="asset/js/owl.carousel.min.js"></script>
<script src="asset/js/jquery.stellar.min.js"></script>
<script src="asset/js/jquery.countdown.min.js"></script>
<script src="asset/js/bootstrap-datepicker.min.js"></script>
<script src="asset/js/jquery.easing.1.3.js"></script>
<script src="asset/js/aos.js"></script>
<script src="asset/js/jquery.fancybox.min.js"></script>
<script src="asset/js/jquery.sticky.js"></script>
<script src="asset/js/main.js"></script>

</body>
</html>
