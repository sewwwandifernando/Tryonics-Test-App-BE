<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Posts Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #2196F3;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .body-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Posts Report</h1>
        <p>Generated on: {{ date('F d, Y h:i A') }}</p>
        <p>Total Posts: {{ count($posts) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Body</th>
                <th>Author</th>
                <th>Categories</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach($posts as $post)
            <tr>
                <td>{{ $post->id }}</td>
                <td>{{ $post->title }}</td>
                <td class="body-text">{{ Str::limit($post->body, 50) }}</td>
                <td>{{ $post->user->name ?? 'N/A' }}</td>
                <td>{{ $post->categories->pluck('name')->implode(', ') ?: 'N/A' }}</td>
                <td>{{ $post->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Your Application Name. All rights reserved.</p>
    </div>
</body>
</html>