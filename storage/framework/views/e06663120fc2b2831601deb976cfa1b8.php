

<?php $__env->startSection('content'); ?>
<div class="container">
    <header>
        <div class="logo">
            <i class="fas fa-link"></i>
            <h1>TinyLink</h1>
        </div>
        <p class="tagline">Transform long URLs into short, memorable links</p>
    </header>

    <div class="card">
        <form method="POST" action="<?php echo e(route('urls.store')); ?>" id="url-form">
            <?php echo csrf_field(); ?>
            <div class="input-group">
                <input type="url" id="long-url" name="original_url" placeholder="Paste your long URL here..." required>
                <button type="submit" id="shorten-btn">
                    <i class="fas fa-compress-alt"></i> Shorten
                </button>
            </div>
            <?php if($errors->any()): ?>
                <div id="error-message" class="error">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>
        </form>

        <?php if(session('success')): ?>
        <div id="short-url-container" class="result">
            <div class="result-header">
                <h3>Your shortened URL</h3>
                <span class="badge">New</span>
            </div>
            <div class="url-display">
                <a href="<?php echo e(url('/'.session('shortCode'))); ?>" id="short-url" target="_blank"><?php echo e(url('/'.session('shortCode'))); ?></a>
                <button id="copy-btn" class="btn-copy" data-clipboard-text="<?php echo e(url('/'.session('shortCode'))); ?>">
                    <i class="far fa-copy"></i> Copy
                </button>
            </div>
            <div class="qr-container">
                <button id="qr-btn" class="btn-secondary">
                    <i class="fas fa-qrcode"></i> Generate QR Code
                </button>
                <div id="qr-code" data-url="<?php echo e(url('/'.session('shortCode'))); ?>"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="stats-container">
        <h2><i class="fas fa-chart-line"></i> Your Links</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Short URL</th>
                        <th>Original URL</th>
                        <th>Clicks</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="urlTable">
                    <?php $__currentLoopData = $urls ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><a href="<?php echo e(url('/'.$url->short_code)); ?>" target="_blank"><?php echo e(url('/'.$url->short_code)); ?></a></td>
                        <td class="original-url"><?php echo e(Str::limit($url->original_url, 50)); ?></td>
                        <td><?php echo e($url->clicks); ?></td>
                        <td><?php echo e($url->created_at->format('M d, Y')); ?></td>
                        <td>
                            <a href="<?php echo e(route('urls.stats', $url->short_code)); ?>" class="btn-action" title="View Stats">
                                <i class="fas fa-chart-pie"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('urls.destroy', $url->short_code)); ?>" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn-action delete" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php if(!isset($urls) || count($urls) === 0): ?>
        <div id="empty-state" class="empty-state">
            <i class="far fa-folder-open"></i>
            <p>No URLs shortened yet</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('styles'); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Base styles */
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

/* Header styles */
header {
    text-align: center;
    margin-bottom: 2rem;
}

.logo {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
}

.logo i {
    font-size: 1.8rem;
    color: var(--primary-color);
    margin-right: 0.5rem;
}

.logo h1 {
    margin: 0;
    font-size: 2rem;
    color: var(--text-color);
}

.tagline {
    color: var(--text-light);
    font-size: 1.1rem;
    margin: 0;
}

/* Card styles */
.card {
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

/* Form styles */
.input-group {
    display: flex;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    transition: border-color 0.2s;
}

.input-group:focus-within {
    border-color: var(--primary-color);
}

.input-group input {
    flex-grow: 1;
    border: none;
    padding: 0.8rem 1rem;
    font-size: 1rem;
    outline: none;
}

.input-group button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 0 1.5rem;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s;
}

.input-group button:hover {
    background-color: var(--primary-hover);
}

.error {
    color: var(--error-color);
    font-size: 0.9rem;
    margin-top: 0.5rem;
    display: none;
}

.error:not(:empty) {
    display: block;
}

/* Result styles */
.result {
    margin-top: 1.5rem;
    display: none;
}

.result:not(:empty) {
    display: block;
}

.result-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.result-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.badge {
    background-color: var(--primary-color);
    color: white;
    font-size: 0.75rem;
    padding: 0.2rem 0.6rem;
    border-radius: 99px;
    margin-left: 0.7rem;
}

.url-display {
    display: flex;
    align-items: center;
    background-color: var(--background);
    padding: 0.7rem 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.url-display a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex-grow: 1;
    margin-right: 1rem;
}

.btn-copy {
    background-color: transparent;
    border: 1px solid var(--border-color);
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    color: var(--text-color);
    font-size: 0.9rem;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-copy:hover {
    background-color: var(--background);
    border-color: var(--text-light);
}

.qr-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.btn-secondary {
    background-color: transparent;
    border: 1px solid var(--border-color);
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    color: var(--text-color);
    font-size: 0.9rem;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background-color: var(--background);
    border-color: var(--text-light);
}

#qr-code {
    margin-top: 1rem;
    display: none;
    text-align: center;
}

#qr-code img {
    max-width: 180px;
}

/* Stats container */
.stats-container {
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
}

.stats-container h2 {
    font-size: 1.3rem;
    margin-top: 0;
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
}

.stats-container h2 i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead tr {
    background-color: var(--background);
}

th, td {
    padding: 0.8rem;
    text-align: left;
}

th {
    font-weight: 600;
    color: var(--text-light);
    font-size: 0.9rem;
}

tbody tr {
    border-bottom: 1px solid var(--border-color);
}

tbody tr:last-child {
    border-bottom: none;
}

td a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.original-url {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-action {
    background: transparent;
    border: none;
    color: var(--text-light);
    padding: 0.3rem 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
    transition: color 0.2s;
    text-decoration: none;
    display: inline-block;
}

.btn-action:hover {
    color: var(--primary-color);
}

.btn-action.delete:hover {
    color: var(--error-color);
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-light);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
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
    .original-url {
        max-width: 150px;
    }
}

@media (max-width: 576px) {
    .input-group {
        flex-direction: column;
    }
    
    .input-group input {
        width: 100%;
        border-bottom: 1px solid var(--border-color);
    }
    
    .input-group button {
        width: 100%;
        padding: 0.8rem;
    }
    
    .url-display {
        flex-direction: column;
        align-items: stretch;
    }
    
    .url-display a {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
    
    .btn-copy {
        width: 100%;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/qrcode.js/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy button functionality
    const copyBtn = document.getElementById('copy-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const url = this.getAttribute('data-clipboard-text');
            navigator.clipboard.writeText(url).then(() => {
                copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="far fa-copy"></i> Copy';
                }, 2000);
            });
        });
    }

    // QR Code functionality
    const qrBtn = document.getElementById('qr-btn');
    const qrCodeDiv = document.getElementById('qr-code');
    
    if (qrBtn && qrCodeDiv) {
        qrBtn.addEventListener('click', function() {
            if (qrCodeDiv.style.display === 'block') {
                qrCodeDiv.style.display = 'none';
                qrBtn.innerHTML = '<i class="fas fa-qrcode"></i> Generate QR Code';
            } else {
                const url = qrCodeDiv.getAttribute('data-url');
                qrCodeDiv.innerHTML = '';
                
                new QRCode(qrCodeDiv, {
                    text: url,
                    width: 180,
                    height: 180
                });
                
                qrCodeDiv.style.display = 'block';
                qrBtn.innerHTML = '<i class="fas fa-minus-circle"></i> Hide QR Code';
            }
        });
    }
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\konth\Desktop\PHP Projects\Laravel\url-shortener\resources\views/urls/index.blade.php ENDPATH**/ ?>