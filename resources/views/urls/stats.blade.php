@extends('layouts.app')

@section('content')
<div class="container">
    <div class="stat-card">
        <div class="card-header">
            <h2><i class="fas fa-chart-pie"></i> URL Statistics</h2>
            <a href="{{ url('/') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Shortener</a>
        </div>
        
        <div class="stat-section">
            <h3>URL Details</h3>
            <div class="detail-row">
                <div class="detail-label">Original URL:</div>
                <div class="detail-value"><a href="{{ $url->original_url }}" target="_blank">{{ $url->original_url }}</a></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Short URL:</div>
                <div class="detail-value"><a href="{{ url($url->short_code) }}" target="_blank">{{ url($url->short_code) }}</a></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Created:</div>
                <div class="detail-value">{{ $url->created_at->format('F j, Y, g:i a') }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Expires:</div>
                <div class="detail-value">
                    @if($url->expires_at)
                        {{ \Carbon\Carbon::parse($url->expires_at)->format('F j, Y') }}
                    @else
                        Never
                    @endif
                </div>
            </div>
        </div>

        <div class="stat-section">
            <h3>Performance</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-number">{{ $url->clicks }}</div>
                    <div class="stat-label">Total Clicks</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-number">{{ $url->created_at->diffInDays() }}</div>
                    <div class="stat-label">Days Active</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-number">
                        @if($url->created_at->diffInDays() > 0)
                            {{ round($url->clicks / $url->created_at->diffInDays(), 1) }}
                        @else
                            {{ $url->clicks }}
                        @endif
                    </div>
                    <div class="stat-label">Clicks/Day</div>
                </div>
            </div>
        </div>
        
        <div class="stat-section">
            <h3>Recent Clicks</h3>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>IP Address</th>
                        <th>Device</th>
                        <th>Referrer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($url->stats()->latest()->take(10)->get() as $stat)
                    <tr>
                        <td>{{ $stat->created_at->format('M j, Y g:i a') }}</td>
                        <td>{{ $stat->ip_address ?? 'Unknown' }}</td>
                        <td>{{ $stat->device_type ?? 'Unknown' }}</td>
                        <td>{{ $stat->referrer ?? 'Direct' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="empty-stats">No click data available yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="qr-section">
            <h3>QR Code</h3>
            <div id="stats-qr" data-url="{{ url($url->short_code) }}"></div>
            <button id="download-qr" class="btn-primary">
                <i class="fas fa-download"></i> Download QR Code
            </button>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary-color: #4f46e5;
    --primary-hover: #4338ca;
    --text-color: #1f2937;
    --text-light: #6b7280;
    --background: #f9fafb;
    --card-bg: #ffffff;
    --border-color: #e5e7eb;
    --success-color: #10b981;
    --error-color: #ef4444;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: var(--background);
    color: var(--text-color);
    line-height: 1.5;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* Stats Card */
.stat-card {
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1rem;
}

.card-header h2 {
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
}

.card-header h2 i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    background-color: var(--background);
    color: var(--text-color);
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.btn-back i {
    margin-right: 0.5rem;
}

.btn-back:hover {
    background-color: var(--border-color);
}

/* Stats Sections */
.stat-section {
    margin-bottom: 2rem;
}

.stat-section h3 {
    font-size: 1.2rem;
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--text-color);
}

/* Detail Rows */
.detail-row {
    display: flex;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    flex: 0 0 120px;
    font-weight: 600;
    color: var(--text-light);
}

.detail-value {
    flex: 1;
    word-break: break-all;
}

.detail-value a {
    color: var(--primary-color);
    text-decoration: none;
}

.detail-value a:hover {
    text-decoration: underline;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.stat-box {
    background-color: var(--background);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-label {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

/* Stats Table */
.stats-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.stats-table th,
.stats-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.stats-table th {
    font-weight: 600;
    color: var(--text-light);
    font-size: 0.9rem;
}

.empty-stats {
    text-align: center;
    color: var(--text-light);
    padding: 2rem 0;
}

/* QR Section */
.qr-section {
    text-align: center;
}

#stats-qr {
    margin: 1rem auto;
    max-width: 200px;
}

#stats-qr img {
    width: 100%;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    transition: background-color 0.2s;
    margin-top: 1rem;
}

.btn-primary i {
    margin-right: 0.5rem;
}

.btn-primary:hover {
    background-color: var(--primary-hover);
}

/* Footer */
footer {
    text-align: center;
    margin-top: 2rem;
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Media queries */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .btn-back {
        margin-top: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .detail-row {
        flex-direction: column;
    }
    
    .detail-label {
        margin-bottom: 0.25rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-table th:nth-child(3),
    .stats-table td:nth-child(3),
    .stats-table th:nth-child(4),
    .stats-table td:nth-child(4) {
        display: none;
    }
}
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode.js/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate QR code
    const qrContainer = document.getElementById('stats-qr');
    const url = qrContainer.getAttribute('data-url');
    
    new QRCode(qrContainer, {
        text: url,
        width: 200,
        height: 200
    });
    
    // Download QR code
    document.getElementById('download-qr').addEventListener('click', function() {
        const img = qrContainer.querySelector('img');
        if (img) {
            const link = document.createElement('a');
            link.href = img.src;
            link.download = 'tinylink_qrcode.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });
});
</script>
@endsection