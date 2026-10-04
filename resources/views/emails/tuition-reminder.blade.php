<p>Hello {{ $studentName }},</p>
<p>{{ $notice['label'] }} for invoice <strong>{{ $invoiceNumber }}</strong>.</p>
<p>Amount remaining for this deadline: <strong>₦{{ number_format($notice['amount']/100, 2) }}</strong><br>Deadline: {{ $notice['date'] }}</p>
<p><a href="{{ $paymentUrl }}">Sign in to view your invoice and pay</a>.</p>
<p>If you have already paid, check the transaction status in your portal. Contact the bursary if your payment has not been reflected.</p>
