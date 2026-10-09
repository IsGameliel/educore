<?php
// Read-only gateway diagnostic. Never prints credentials or transaction payloads.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$key = config('services.paystack.secret_key');
if (! $key) { fwrite(STDERR, "Paystack is not configured.\n"); exit(1); }
try {
    $response = Illuminate\Support\Facades\Http::withToken($key)->acceptJson()->connectTimeout(5)->timeout(15)
        ->get('https://api.paystack.co/integration/payment_session_timeout');
    if (! $response->successful() || $response->json('status') !== true) {
        fwrite(STDERR, 'Paystack credential check failed (HTTP '.$response->status().").\n"); exit(1);
    }
    echo 'Paystack authenticated successfully ('.(str_starts_with($key, 'sk_test_') ? 'test' : 'live')." environment).\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Paystack connectivity check failed: '.get_class($exception).".\n");
    $previous = $exception->getPrevious();
    if ($previous instanceof \GuzzleHttp\Exception\ConnectException) {
        $context = $previous->getHandlerContext();
        fwrite(STDERR, 'cURL error '.($context['errno'] ?? 'unknown').': '.($context['error'] ?? 'No transport detail available')."\n");
    }
    exit(1);
}
