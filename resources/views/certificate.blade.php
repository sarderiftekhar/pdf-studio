<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate — {{ $recipient }}</title>
    <style>
        body { font-family: serif; text-align: center; padding: 60px 40px; color: #333; }
        .border { border: 3px double #666; padding: 60px 40px; }
        h1 { font-size: 36px; color: #1a1a1a; margin-bottom: 10px; letter-spacing: 4px; text-transform: uppercase; }
        .subtitle { font-size: 16px; color: #666; margin-bottom: 40px; }
        .recipient { font-size: 28px; color: #2a2a2a; margin: 20px 0; font-style: italic; }
        .achievement { font-size: 18px; margin: 10px 0 40px; }
        .footer { margin-top: 40px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="border">
        <h1>Certificate</h1>
        <div class="subtitle">of Achievement</div>

        <p>This is to certify that</p>
        <div class="recipient">{{ $recipient }}</div>
        <div class="achievement">has successfully completed: <strong>{{ $achievement }}</strong></div>

        <div class="footer">
            <div>{{ $date }}</div>
            <div>{{ $issuer }}</div>
        </div>
    </div>
</body>
</html>
