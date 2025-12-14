<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة حالة النظام - {{ $appName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 30px 40px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .status-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .section-title {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .status-card {
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: #3498db;
            transition: width 0.3s ease;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .status-card:hover::before {
            width: 100%;
            opacity: 0.1;
        }
        
        .status-card.success::before {
            background: #27ae60;
        }
        
        .status-card.error::before {
            background: #e74c3c;
        }
        
        .status-card.warning::before {
            background: #f39c12;
        }
        
        .status-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #7f8c8d;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-label::before {
            content: '●';
            font-size: 8px;
        }
        
        .status-value {
            font-size: 22px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .status-value.success {
            color: #27ae60;
        }
        
        .status-value.error {
            color: #e74c3c;
        }
        
        .status-value.warning {
            color: #f39c12;
        }
        
        .error-message {
            margin-top: 12px;
            padding: 10px;
            background: #fee;
            border-right: 3px solid #e74c3c;
            border-radius: 5px;
            font-size: 13px;
            color: #c0392b;
        }
        
        .logs-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .logs-container {
            background: #1e1e1e;
            border-radius: 8px;
            padding: 20px;
            max-height: 600px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
        }
        
        .logs-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .logs-container::-webkit-scrollbar-track {
            background: #2d2d2d;
            border-radius: 4px;
        }
        
        .logs-container::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }
        
        .logs-container::-webkit-scrollbar-thumb:hover {
            background: #777;
        }
        
        .log-entry {
            padding: 8px 0;
            border-bottom: 1px solid #333;
            color: #d4d4d4;
            word-wrap: break-word;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-entry.error {
            color: #f48771;
        }
        
        .log-entry.warning {
            color: #dcdcaa;
        }
        
        .log-entry.info {
            color: #4ec9b0;
        }
        
        .log-entry.debug {
            color: #9cdcfe;
        }
        
        .log-entry.critical {
            color: #f48771;
            background: rgba(244, 135, 113, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
        }
        
        .log-timestamp {
            color: #858585;
            margin-left: 10px;
        }
        
        .no-logs {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .footer {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 20px;
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        @media (max-width: 768px) {
            .status-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .logs-container {
                max-height: 400px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 لوحة حالة النظام</h1>
            <div class="subtitle">معلومات شاملة عن حالة التطبيق والخدمات</div>
        </div>
        
        <div class="status-section">
            <div class="section-title">حالة النظام</div>
            
            <div class="status-grid">
                <div class="status-card">
                    <div class="status-label">اسم التطبيق</div>
                    <div class="status-value">{{ $appName }}</div>
                </div>
                
                <div class="status-card warning">
                    <div class="status-label">البيئة</div>
                    <div class="status-value warning">
                        <span class="badge badge-warning">{{ ucfirst($environment) }}</span>
                    </div>
                </div>
                
                <div class="status-card success">
                    <div class="status-label">حالة التطبيق</div>
                    <div class="status-value success">
                        <span class="badge badge-success">{{ $appStatus }}</span>
                    </div>
                </div>
                
                <div class="status-card {{ $databaseStatus === 'Connected' ? 'success' : 'error' }}">
                    <div class="status-label">اتصال قاعدة البيانات</div>
                    <div class="status-value {{ $databaseStatus === 'Connected' ? 'success' : 'error' }}">
                        @if($databaseStatus === 'Connected')
                            <span class="badge badge-success">{{ $databaseStatus }}</span>
                        @else
                            <span class="badge badge-error">{{ $databaseStatus }}</span>
                        @endif
                    </div>
                    @if($databaseError)
                        <div class="error-message">{{ $databaseError }}</div>
                    @endif
                </div>
                
                <div class="status-card">
                    <div class="status-label">وقت الخادم</div>
                    <div class="status-value">{{ $serverTime }}</div>
                </div>
            </div>
        </div>
        
        <div class="logs-section">
            <div class="section-title">📝 سجلات المشروع</div>
            
            @if(empty($logs))
                <div class="no-logs">
                    لا توجد سجلات متاحة حالياً
                </div>
            @else
                <div class="logs-container">
                    @foreach($logs as $log)
                        @php
                            $logEntry = is_array($log) ? ($log['error'] ?? '') : $log;
                            $logClass = 'info';
                            
                            if (is_string($logEntry)) {
                                if (stripos($logEntry, 'error') !== false || stripos($logEntry, 'exception') !== false) {
                                    $logClass = 'error';
                                } elseif (stripos($logEntry, 'warning') !== false) {
                                    $logClass = 'warning';
                                } elseif (stripos($logEntry, 'critical') !== false || stripos($logEntry, 'emergency') !== false) {
                                    $logClass = 'critical';
                                } elseif (stripos($logEntry, 'debug') !== false) {
                                    $logClass = 'debug';
                                }
                            }
                        @endphp
                        <div class="log-entry {{ $logClass }}">
                            {!! nl2br(e($logEntry)) !!}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        
        <div class="footer">
            <div>لوحة حالة النظام - {{ $appName }}</div>
            <div style="margin-top: 5px; font-size: 12px;">آخر تحديث: {{ $serverTime }}</div>
        </div>
    </div>
</body>
</html>
