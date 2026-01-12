<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1e3b3b;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info {
            margin-bottom: 20px;
        }
        .info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #1e3b3b;
            color: white;
            font-weight: bold;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e3b3b;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>{{ $data['company_name'] }}</p>
        <p>Generated on: {{ $data['report_date'] }}</p>
    </div>

    @if($type === 'status')
        @foreach($data['grouped_assets'] as $group)
            <div class="section">
                <div class="section-title">
                    {{ $group['status'] }} ({{ $group['count'] }} assets)
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Asset Code</th>
                            <th>Type</th>
                            <th>Model</th>
                            <th>Purchase Date</th>
                            <th>Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group['assets'] as $asset)
                            <tr>
                                <td>{{ $asset['asset_code'] }}</td>
                                <td>{{ $asset['type'] }}</td>
                                <td>{{ $asset['model'] }}</td>
                                <td>{{ $asset['purchase_date'] }}</td>
                                <td>${{ $asset['cost'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @elseif($type === 'maintenance')
        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Type</th>
                        <th>Model</th>
                        <th>Purchase Date</th>
                        <th>Purchase Cost</th>
                        <th>Total Maintenance Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['assets'] as $asset)
                        <tr>
                            <td>{{ $asset['asset_code'] }}</td>
                            <td>{{ $asset['type'] }}</td>
                            <td>{{ $asset['model'] }}</td>
                            <td>{{ $asset['purchase_date'] }}</td>
                            <td>${{ $asset['purchase_cost'] }}</td>
                            <td><strong>${{ $asset['total_maintenance_cost'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif($type === 'warranty')
        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Type</th>
                        <th>Model</th>
                        <th>Purchase Date</th>
                        <th>Warranty End</th>
                        <th>Days Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['assets'] as $asset)
                        <tr>
                            <td>{{ $asset['asset_code'] }}</td>
                            <td>{{ $asset['type'] }}</td>
                            <td>{{ $asset['model'] }}</td>
                            <td>{{ $asset['purchase_date'] }}</td>
                            <td>{{ $asset['warranty_end'] }}</td>
                            <td><strong>{{ $asset['days_remaining'] }} days</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated automatically by the HRM System</p>
    </div>
</body>
</html>

