<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMI Weather API</title>
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

        header .version {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            margin-left: 10px;
            vertical-align: middle;
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

        .endpoint:last-of-type {
            border-bottom: none;
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
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

        .method.post {
            background-color: #49cc90;
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

        th, td {
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

        .try-it {
            margin-top: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }

        .try-it-header {
            background: #f8f9fa;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }

        .try-it-header:hover {
            background: #e9ecef;
        }

        .try-it-header h4 {
            margin: 0;
            color: #495057;
            font-size: 0.95em;
        }

        .try-it-toggle {
            font-size: 0.8em;
            color: #667eea;
            font-weight: 600;
        }

        .try-it-body {
            display: none;
            padding: 16px;
            border-top: 1px solid #e0e0e0;
        }

        .try-it-body.open {
            display: block;
        }

        .try-it-field {
            margin-bottom: 12px;
        }

        .try-it-field label {
            display: block;
            font-weight: 600;
            font-size: 0.9em;
            margin-bottom: 4px;
            color: #495057;
        }

        .try-it-field label .field-in {
            font-weight: 400;
            color: #888;
            font-size: 0.9em;
        }

        .try-it-field input,
        .try-it-field select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.95em;
            font-family: inherit;
        }

        .try-it-field input:focus,
        .try-it-field select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.15);
        }

        .try-it-field .field-hint {
            font-size: 0.8em;
            color: #888;
            margin-top: 2px;
        }

        .try-it-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
        }

        .try-it-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 24px;
            border-radius: 4px;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
        }

        .try-it-btn:hover {
            background: #5a6fd6;
        }

        .try-it-url {
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            color: #667eea;
            word-break: break-all;
        }

        .try-it-response {
            margin-top: 16px;
            display: none;
        }

        .try-it-response.visible {
            display: block;
        }

        .try-it-response-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .try-it-status {
            font-weight: 600;
            font-size: 0.9em;
            padding: 2px 8px;
            border-radius: 3px;
        }

        .try-it-status.ok {
            background: #d4edda;
            color: #155724;
        }

        .try-it-status.error {
            background: #f8d7da;
            color: #721c24;
        }

        .try-it-response pre {
            margin: 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .info-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-section ul {
            margin-left: 20px;
            color: #666;
        }

        .info-section ul li {
            margin-bottom: 8px;
        }

        .info-section p {
            color: #666;
            margin-bottom: 10px;
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
            <h1>
                DMI Weather API
            </h1>
            <p>Danish Meteorological Institute Weather Data Proxy</p>
        </div>
    </header>

    <div class="container">
        <main>
            <div class="info-section">
                <h2>Location Formats</h2>
                <p>All endpoints accept a <code>location</code> path parameter in these formats:</p>
                <table>
                    <thead>
                        <tr>
                            <th>Format</th>
                            <th>Example</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>City Name</td>
                            <td><code>copenhagen</code>, <code>k&oslash;benhavn</code>, <code>aarhus</code></td>
                            <td>Danish city names (case-insensitive)</td>
                        </tr>
                        <tr>
                            <td>Postal Code</td>
                            <td><code>1000</code>, <code>8000</code>, <code>5000</code></td>
                            <td>4-digit Danish postal codes</td>
                        </tr>
                        <tr>
                            <td>Coordinates</td>
                            <td><code>55.6761,12.5683</code></td>
                            <td>Latitude and longitude (comma-separated)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="info-section">
                <h2>Wind Chill</h2>
                <p>Wind chill is automatically calculated using the North American and UK wind chill index formula when:</p>
                <ul>
                    <li><strong>Temperature</strong> is at or below 10&deg;C</li>
                    <li><strong>Wind speed</strong> is above 1.3 m/s (4.68 km/h)</li>
                </ul>
                <p>If conditions don't meet these criteria, wind chill fields are omitted from the response.</p>
                <p><strong>Formula:</strong></p>
                <pre><code>Wind Chill (&deg;C) = 13.12 + 0.6215T - 11.37V^0.16 + 0.3965TV^0.16

Where:
  T = Air temperature (&deg;C)
  V = Wind speed (km/h)</code></pre>
            </div>

            @php
                $currentTtl = config('services.dmi.cache_ttl.current');
                $forecastTtl = config('services.dmi.cache_ttl.forecast');
                $historicalTtl = config('services.dmi.cache_ttl.historical', 86400);
                $geocodingTtl = config('services.dmi.cache_ttl.geocoding', 86400);

                $formatTtl = function($seconds) {
                    if ($seconds >= 3600) {
                        $hours = floor($seconds / 3600);
                        return $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
                    }
                    if ($seconds >= 60) {
                        $minutes = floor($seconds / 60);
                        return $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
                    }
                    return $seconds . ' ' . ($seconds === 1 ? 'second' : 'seconds');
                };
            @endphp

            <div class="info-section">
                <h2>Caching</h2>
                <ul>
                    <li>Current weather: <strong>{{ $formatTtl($currentTtl) }}</strong></li>
                    <li>Forecast: <strong>{{ $formatTtl($forecastTtl) }}</strong></li>
                    <li>Historical: <strong>{{ $formatTtl($historicalTtl) }}</strong></li>
                    <li>Geocoding: <strong>{{ $formatTtl($geocodingTtl) }}</strong></li>
                </ul>
            </div>

            {{-- Endpoints --}}
            <h2 style="margin-top: 10px;">Endpoints</h2>

            @foreach ($endpoints as $endpoint)
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method {{ strtolower($endpoint['method']) }}">{{ $endpoint['method'] }}</span>
                        <span class="endpoint-path">{{ $endpoint['uri'] }}</span>
                    </div>
                    <p class="endpoint-description">{{ $endpoint['description'] }}</p>

                    @if (!empty($endpoint['path_parameters']))
                        <h3>Path Parameters</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($endpoint['path_parameters'] as $param)
                                    <tr>
                                        <td><code>{{ $param['name'] }}</code></td>
                                        <td>{{ $param['type'] }}</td>
                                        <td>{{ $param['description'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if (!empty($endpoint['query_parameters']))
                        <h3>Query Parameters</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($endpoint['query_parameters'] as $param)
                                    <tr>
                                        <td><code>{{ $param['name'] }}</code></td>
                                        <td>{{ $param['type'] }}</td>
                                        <td>
                                            <span class="badge {{ $param['required'] ? 'required' : 'optional' }}">
                                                {{ $param['required'] ? 'Required' : 'Optional' }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $param['description'] }}
                                            @if ($param['enum'])
                                                <br>Values: @foreach ($param['enum'] as $val)<code>{{ $val }}</code>{{ !$loop->last ? ', ' : '' }}@endforeach
                                            @endif
                                            @if (isset($param['default']))
                                                <br>Default: <code>{{ $param['default'] }}</code>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if ($endpoint['example_response'])
                        <h3>Example Response</h3>
                        <pre><code>{{ json_encode($endpoint['example_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                    @endif

                    {{-- Try it out --}}
                    @php
                        $uriPattern = preg_replace('/\{(\w+)\}/', '{$1}', $endpoint['uri']);
                    @endphp
                    <div class="try-it" data-method="{{ $endpoint['method'] }}" data-path="{{ $uriPattern }}">
                        <div class="try-it-header" onclick="toggleTryIt(this)">
                            <h4>Try it out</h4>
                            <span class="try-it-toggle">Expand</span>
                        </div>
                        <div class="try-it-body">
                            @foreach ($endpoint['path_parameters'] as $param)
                                <div class="try-it-field">
                                    <label>
                                        {{ $param['name'] }}
                                        <span class="field-in">(path)</span>
                                        <span class="badge required" style="font-size: 0.7em; vertical-align: middle;">Required</span>
                                    </label>
                                    <input type="text" data-param-name="{{ $param['name'] }}" data-param-in="path" placeholder="copenhagen">
                                </div>
                            @endforeach

                            @foreach ($endpoint['query_parameters'] as $param)
                                <div class="try-it-field">
                                    <label>
                                        {{ $param['name'] }}
                                        <span class="field-in">(query)</span>
                                        @if ($param['required'])
                                            <span class="badge required" style="font-size: 0.7em; vertical-align: middle;">Required</span>
                                        @endif
                                    </label>
                                    @if ($param['enum'] && $param['type'] === 'array')
                                        <select data-param-name="{{ $param['name'] }}" data-param-in="query" multiple size="{{ min(count($param['enum']), 5) }}">
                                            @foreach ($param['enum'] as $val)
                                                <option value="{{ $val }}">{{ $val }}</option>
                                            @endforeach
                                        </select>
                                        <div class="field-hint">Hold Ctrl/Cmd to select multiple</div>
                                    @elseif ($param['enum'])
                                        <select data-param-name="{{ $param['name'] }}" data-param-in="query">
                                            <option value="">-- select --</option>
                                            @foreach ($param['enum'] as $val)
                                                <option value="{{ $val }}" @selected($val === ($param['default'] ?? ''))>{{ $val }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input
                                            type="{{ $param['type'] === 'integer' ? 'number' : 'text' }}"
                                            data-param-name="{{ $param['name'] }}"
                                            data-param-in="query"
                                            @if (isset($param['min'])) min="{{ $param['min'] }}" @endif
                                            @if (isset($param['max'])) max="{{ $param['max'] }}" @endif
                                            @if (isset($param['default'])) value="{{ $param['default'] }}" @endif
                                        >
                                    @endif
                                </div>
                            @endforeach

                            <div class="try-it-actions">
                                <button class="try-it-btn" onclick="executeTryIt(this)">Execute</button>
                                <span class="try-it-url"></span>
                            </div>

                            <div class="try-it-response">
                                <div class="try-it-response-header">
                                    <span>Response</span>
                                    <span class="try-it-status"></span>
                                </div>
                                <pre><code></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Error responses --}}
            <h2 style="margin-top: 50px;">Error Responses</h2>
            <h3>400 Bad Request</h3>
            <pre><code>{{ json_encode(['error' => 'Unable to geocode location: unknowncity', 'location' => 'unknowncity'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            <h3>422 Unprocessable Entity</h3>
            <pre><code>{{ json_encode(['message' => 'The from field is required.', 'errors' => ['from' => ['The from field is required.']]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
        </main>
    </div>

    <footer>
        <div class="container">
            <p>&copy; {{ date('Y') }} Christian Morgan Andersen</p>
        </div>
    </footer>

    <script>
        const baseUrl = '';

        function toggleTryIt(header) {
            const body = header.nextElementSibling;
            const toggle = header.querySelector('.try-it-toggle');
            body.classList.toggle('open');
            toggle.textContent = body.classList.contains('open') ? 'Collapse' : 'Expand';
        }

        function executeTryIt(btn) {
            const tryIt = btn.closest('.try-it');
            const method = tryIt.dataset.method;
            let path = tryIt.dataset.path;

            const fields = tryIt.querySelectorAll('[data-param-name]');
            const queryParts = [];

            fields.forEach(field => {
                const name = field.dataset.paramName;
                const location = field.dataset.paramIn;
                let value;

                if (field.tagName === 'SELECT' && field.multiple) {
                    value = Array.from(field.selectedOptions).map(o => o.value);
                } else {
                    value = field.value;
                }

                if (location === 'path' && value) {
                    path = path.replace(`{${name}}`, encodeURIComponent(value));
                } else if (location === 'query') {
                    if (Array.isArray(value)) {
                        value.forEach(v => {
                            if (v) queryParts.push(`${name}=${encodeURIComponent(v)}`);
                        });
                    } else if (value) {
                        queryParts.push(`${name}=${encodeURIComponent(value)}`);
                    }
                }
            });

            let url = baseUrl + path;
            if (queryParts.length) url += '?' + queryParts.join('&');

            const urlDisplay = tryIt.querySelector('.try-it-url');
            urlDisplay.textContent = method + ' ' + url;

            const responseDiv = tryIt.querySelector('.try-it-response');
            const statusSpan = responseDiv.querySelector('.try-it-status');
            const codeBlock = responseDiv.querySelector('code');

            btn.disabled = true;
            btn.textContent = 'Loading...';

            fetch(url, { method: method, headers: { 'Accept': 'application/json' } })
                .then(response => {
                    statusSpan.textContent = response.status + ' ' + response.statusText;
                    statusSpan.className = 'try-it-status ' + (response.ok ? 'ok' : 'error');
                    return response.json();
                })
                .then(data => {
                    codeBlock.textContent = JSON.stringify(data, null, 2);
                    responseDiv.classList.add('visible');
                })
                .catch(err => {
                    statusSpan.textContent = 'Error';
                    statusSpan.className = 'try-it-status error';
                    codeBlock.textContent = err.message;
                    responseDiv.classList.add('visible');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Execute';
                });
        }
    </script>
</body>

</html>
