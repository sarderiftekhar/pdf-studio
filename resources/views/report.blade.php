<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; margin: 0; padding: 40px; }
        h1 { font-size: 24px; color: #1a1a1a; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .date { color: #666; margin-bottom: 30px; }
        h2 { font-size: 18px; color: #2a2a2a; margin-top: 30px; }
        p { line-height: 1.6; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="date">{{ $date }}</div>

    @foreach($sections as $section)
    <h2>{{ $section['heading'] }}</h2>
    <p>{{ $section['content'] }}</p>
    @endforeach
</body>
</html>
