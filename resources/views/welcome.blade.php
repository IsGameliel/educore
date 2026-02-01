@extends('layouts.main')

@section('content')
    <div class="intro-section" id="home-section">

        <div class="slide-1" style="background-image: url('asset/images/hero_1.jpg');" data-stellar-background-ratio="0.5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-12">
                        <div class="row align-items-center">
                            <div class="col-lg-6 mb-4">
                                <h1  data-aos="fade-up" data-aos-delay="100">Shape Your Future With World-Class Education</h1>
                                <p class="mb-4"  data-aos="fade-up" data-aos-delay="200">At Eduverse, we connect students to experienced educators and innovative learning tools. Discover a path to excellence tailored to your ambitions.</p>
                                <p data-aos="fade-up" data-aos-delay="300"><a href="{{ url('login') }}" class="btn btn-primary py-3 px-5 btn-pill">Join us</a></p>
                            </div>

                            <div class="col-lg-5 ml-auto" data-aos="fade-up" data-aos-delay="500">
                                <form action="" method="post" class="form-box">
                                    <h3 class="h4 text-black mb-4">Sign Up</h3>
                                    <div class="form-group">
                                        <input type="text" class="form-control" placeholder="Email Addresss">
                                    </div>
                                    <div class="form-group">
                                        <input type="password" class="form-control" placeholder="Password">
                                    </div>
                                    <div class="form-group mb-4">
                                        <input type="password" class="form-control" placeholder="Re-type Password">
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" class="btn btn-primary btn-pill" value="Sign up">
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="site-section" id="features-section">
        <div class="container">
            <div class="row mb-5 justify-content-center">
                <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-delay="">
                    <h2 class="section-title">Why Choose Eduverse?</h2>
                    <p>Eduverse is designed to simplify school management, enhance communication, and promote a seamless educational experience for administrators, teachers, students, and parents.</p>
                </div>
            </div>

            <div class="row mb-5 align-items-center">
                <div class="col-lg-7 mb-5" data-aos="fade-up" data-aos-delay="100">
                    <img src="asset/images/undraw_youtube_tutorial.svg" alt="Centralized Management" class="img-fluid">
                </div>
                <div class="col-lg-4 ml-auto" data-aos="fade-up" data-aos-delay="200">
                    <h2 class="text-black mb-4">Centralized School Management</h2>
                    <p class="mb-4">Manage everything in one place, from student enrollment to fee collection, timetable scheduling, and performance tracking.</p>

                    <div class="d-flex align-items-center custom-icon-wrap mb-3">
                        <span class="custom-icon-inner mr-3"><span class="icon icon-settings"></span></span>
                        <div><h3 class="m-0">Automated Administration</h3></div>
                    </div>

                    <div class="d-flex align-items-center custom-icon-wrap">
                        <span class="custom-icon-inner mr-3"><span class="icon icon-users"></span></span>
                        <div><h3 class="m-0">User Role Management</h3></div>
                    </div>
                </div>
            </div>

            <div class="row mb-5 align-items-center">
                <div class="col-lg-7 mb-5 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="100">
                    <img src="asset/images/undraw_teaching.svg" alt="Real-Time Reporting" class="img-fluid">
                </div>
                <div class="col-lg-4 mr-auto order-2 order-lg-1" data-aos="fade-up" data-aos-delay="200">
                    <h2 class="text-black mb-4">Comprehensive Reporting</h2>
                    <p class="mb-4">Generate insightful reports on student performance, financial records, attendance, and more.</p>

                    <div class="d-flex align-items-center custom-icon-wrap mb-3">
                        <span class="custom-icon-inner mr-3"><span class="icon icon-chart-bar"></span></span>
                        <div><h3 class="m-0">Real-Time Analytics</h3></div>
                    </div>

                    <div class="d-flex align-items-center custom-icon-wrap">
                        <span class="custom-icon-inner mr-3"><span class="icon icon-book"></span></span>
                        <div><h3 class="m-0">Customizable Dashboards</h3></div>
                    </div>
                </div>
            </div>

            <div class="row mb-5 align-items-center">
                <div class="col-lg-7 mb-5" data-aos="fade-up" data-aos-delay="100">
                    <img src="asset/images/undraw_teacher.svg" alt="Seamless Communication" class="img-fluid">
                </div>
                <div class="col-lg-4 ml-auto" data-aos="fade-up" data-aos-delay="200">
                    <h2 class="text-black mb-4">Seamless Communication</h2>
                    <p class="mb-4">Keep everyone connected with real-time messaging, notifications, and updates.</p>

                    <div class="d-flex align-items-center custom-icon-wrap mb-3">
                        <span class="custom-icon-inner mr-3"><span class="icon icon-mail"></span></span>
                        <div><h3 class="m-0">Instant Notifications</h3></div>
                    </div>

                    <div class="d-flex align-items-center custom-icon-wrap">
                        <span class="custom-icon-inner mr-3"><span class="icon icon-comment"></span></span>
                        <div><h3 class="m-0">Parent-Teacher Communication</h3></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="site-section bg-image overlay" style="background-image: url('asset/images/hero_1.jpg');">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-md-8 text-center testimony">
                    <img src="asset/images/person_4.jpg" alt="Image" class="img-fluid w-25 mb-4 rounded-circle">
                    <h3 class="mb-4">Jerome Jensen</h3>
                    <blockquote>
                        <p>&ldquo; Eduverse has transformed the way we manage our school. From seamless communication to insightful analytics, it’s a complete solution that has simplified our administrative processes and improved the learning experience for students and teachers alike. Highly recommend! &rdquo;</p>
                    </blockquote>
                </div>
            </div>
        </div>
    </div>

    <div class="site-section pb-0">

        <div class="future-blobs">
            <div class="blob_2">
                <img src="asset/images/blob_2.svg" alt="Image">
            </div>
            <div class="blob_1">
                <img src="asset/images/blob_1.svg" alt="Image">
            </div>
        </div>
        <div class="container">
            <div class="row mb-5 justify-content-center" data-aos="fade-up" data-aos-delay="">
                <div class="col-lg-7 text-center">
                    <h2 class="section-title">Why Choose Eduverse</h2>
                    <p>Discover the ultimate solution for managing your school with ease and efficiency. Here’s why Eduverse is the smart choice for educators, administrators, and students.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 ml-auto align-self-start" data-aos="fade-up" data-aos-delay="100">

                    <div class="p-4 rounded bg-white why-choose-us-box">

                        <div class="d-flex align-items-center custom-icon-wrap custom-icon-light mb-3">
                            <div class="mr-3"><span class="custom-icon-inner"><span class="icon icon-graduation-cap"></span></span></div>
                            <div><h3 class="m-0">Comprehensive Student Management</h3></div>
                        </div>

                        <div class="d-flex align-items-center custom-icon-wrap custom-icon-light mb-3">
                            <div class="mr-3"><span class="custom-icon-inner"><span class="icon icon-university"></span></span></div>
                            <div><h3 class="m-0">Real-Time Academic Insights</h3></div>
                        </div>

                        <div class="d-flex align-items-center custom-icon-wrap custom-icon-light mb-3">
                            <div class="mr-3"><span class="custom-icon-inner"><span class="icon icon-graduation-cap"></span></span></div>
                            <div><h3 class="m-0">Effortless Staff Coordination</h3></div>
                        </div>

                        <div class="d-flex align-items-center custom-icon-wrap custom-icon-light mb-3">
                            <div class="mr-3"><span class="custom-icon-inner"><span class="icon icon-university"></span></span></div>
                            <div><h3 class="m-0">Seamless Communication Tools</h3></div>
                        </div>

                        <div class="d-flex align-items-center custom-icon-wrap custom-icon-light mb-3">
                            <div class="mr-3"><span class="custom-icon-inner"><span class="icon icon-graduation-cap"></span></span></div>
                            <div><h3 class="m-0">Advanced Analytics & Reporting</h3></div>
                        </div>

                        <div class="d-flex align-items-center custom-icon-wrap custom-icon-light">
                            <div class="mr-3"><span class="custom-icon-inner"><span class="icon icon-university"></span></span></div>
                            <div><h3 class="m-0">User-Friendly Interface</h3></div>
                        </div>

                    </div>

                </div>
                <div class="col-lg-7 align-self-end" data-aos="fade-left" data-aos-delay="200">
                    <img src="asset/images/person_transparent.png" alt="Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>



    <div class="site-section bg-light" id="contact-section">
        <div class="container">

            <div class="row justify-content-center">
                <div class="col-md-7">



                    <h2 class="section-title mb-3">Message Us</h2>
                    <p class="mb-5">Natus totam voluptatibus animi aspernatur ducimus quas obcaecati mollitia quibusdam temporibus culpa dolore molestias blanditiis consequuntur sunt nisi.</p>

                    <form method="post" data-aos="fade">
                        <div class="form-group row">
                            <div class="col-md-6 mb-3 mb-lg-0">
                                <input type="text" class="form-control" placeholder="First name">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="Last name">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <input type="text" class="form-control" placeholder="Subject">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <input type="email" class="form-control" placeholder="Email">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-12">
                                <textarea class="form-control" id="" cols="30" rows="10" placeholder="Write your message here."></textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6">

                                <input type="submit" class="btn btn-primary py-3 px-5 btn-block btn-pill" value="Send Message">
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
    @endsection
