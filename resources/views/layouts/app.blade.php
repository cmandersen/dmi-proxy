<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DMI Weather API')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        main {
            background: white;
            margin-top: 30px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .endpoint {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e0e0e0;
        }

        .endpoint:last-child {
            border-bottom: none;
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .method {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85em;
            text-transform: uppercase;
        }

        .method.get {
            background-color: #61affe;
            color: white;
        }

        .endpoint-path {
            font-family: 'Courier New', monospace;
            font-size: 1.1em;
            color: #667eea;
            font-weight: 500;
        }

        .endpoint-description {
            color: #666;
            margin-bottom: 20px;
        }

        h2 {
            color: #2c3e50;
            margin: 30px 0 15px 0;
            font-size: 1.3em;
        }

        h3 {
            color: #34495e;
            margin: 20px 0 10px 0;
            font-size: 1.1em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        code {
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #e83e8c;
        }

        pre {
            background-color: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 15px 0;
        }

        pre code {
            background: none;
            color: inherit;
            padding: 0;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.required {
            background-color: #ffc107;
            color: #000;
        }

        .badge.optional {
            background-color: #6c757d;
            color: white;
        }

        footer {
            text-align: center;
            padding: 30px 0;
            color: #666;
            margin-top: 40px;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>@yield('header', 'DMI Weather API')</h1>
            <p>@yield('subtitle', 'Danish Meteorological Institute Weather Data Proxy')</p>
        </div>
    </header>

    <div class="container">
        <main>
            @yield('content')
        </main>
    </div>

    <footer>
        <div class="container">
            <p>&copy; {{ date('Y') }} Christian Morgan Andersen</p>
        </div>
    </footer>
</body>

</html>
