<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance {{ $success ? 'Marked' : 'Not Marked' }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
        }
        .card {
            max-width: 360px;
            width: 90%;
            padding: 2rem;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .icon {
            font-size: 3rem;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
        }
        .identifier {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .message {
            margin-top: 1rem;
            font-size: 0.95rem;
            color: {{ $success ? '#166534' : '#991b1b' }};
        }
        .time {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{{ $success ? '✅' : '⚠️' }}</div>
        <div class="name">{{ $name }}</div>
        <div class="identifier">{{ $identifier }}</div>
        <div class="message">{{ $message }}</div>
        @if ($markedAt)
            <div class="time">Checked in at {{ $markedAt }}</div>
        @endif
    </div>
</body>
</html>
