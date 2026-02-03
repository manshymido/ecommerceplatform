<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order {{ $orderNumber }} - {{ $status }}</title>
</head>
<body>
    <h2>Order {{ $orderNumber }}</h2>
    <p>Status: <strong>{{ ucfirst($status) }}</strong></p>
    @if($reason)
        <p>Note: {{ $reason }}</p>
    @endif
    <p>Thank you for your order.</p>
</body>
</html>
