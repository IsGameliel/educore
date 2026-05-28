<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Error...</title>
    <link rel="stylesheet" href="{{ asset('asset/css/bootstrap/bootstrap.css')}}">

    <style>
        body{
            background: ghostwhite;
        }
        h2{
            font-size: 90px;
        }
        .content{
            text-align: center;
            padding-top: 19rem;
        }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
     </div>
</body>
</html>
