document.addEventListener("DOMContentLoaded", function () {
    // DOM Elements
    const urlForm = document.getElementById("url-form");
    const longUrlInput = document.getElementById("long-url");
    const shortUrlContainer = document.getElementById("short-url-container");
    const shortUrlLink = document.getElementById("short-url");
    const copyButton = document.getElementById("copy-btn");
    const errorMessage = document.getElementById("error-message");
    const qrButton = document.getElementById("qr-btn");
    const qrCodeDiv = document.getElementById("qr-code");
    const urlTable = document.getElementById("urlTable");
    const emptyState = document.getElementById("empty-state");
    
    // API endpoint
    const API_ENDPOINT = 'http://127.0.0.1:8000/shorten';
    
    // Load saved URLs from localStorage
    let savedUrls = JSON.parse(localStorage.getItem("tinylink_urls")) || [];
    updateUrlTable();
    
    // 📌 Function to Validate URL
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (error) {
            return false;
        }
    }

    // 📌 Handle Form Submission
    urlForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        const longUrl = longUrlInput.value.trim();

        // UI feedback - disable button and show loading state
        const submitButton = document.getElementById("shorten-btn");
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitButton.disabled = true;

        if (!isValidUrl(longUrl)) {
            showError("❌ Please enter a valid URL including http:// or https://");
            resetButton();
            return;
        }

        errorMessage.style.display = "none";

        try {
            // API call to shorten URL
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ long_url: longUrl })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || "Failed to shorten URL");
            }
            
            // Process API response
            const shortUrl = data.shortened_url;
            const shortCode = shortUrl.split('/').pop();
            
            // Save URL to localStorage for tracking
            const newUrl = {
                id: Date.now(),
                shortCode: shortCode,
                shortUrl: shortUrl,
                longUrl: longUrl,
                clicks: 0,
                dateCreated: new Date().toISOString()
            };
            
            savedUrls.unshift(newUrl);
            localStorage.setItem("tinylink_urls", JSON.stringify(savedUrls));
            
            // Display the result
            showShortUrl(shortUrl);
            updateUrlTable();
            longUrlInput.value = "";

        } catch (error) {
            showError(`❌ ${error.message || "Server error. Try again later."}`);
            console.error("Error shortening URL:", error);
        }
        
        resetButton();
        
        function resetButton() {
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
        }
    });

    // 📌 Copy Shortened URL to Clipboard
    copyButton.addEventListener("click", function () {
        navigator.clipboard.writeText(shortUrlLink.href).then(() => {
            copyButton.innerHTML = '<i class="fas fa-check"></i> Copied!';
            copyButton.style.backgroundColor = "var(--success)";
            copyButton.style.color = "white";
            copyButton.style.borderColor = "var(--success)";
            
            setTimeout(() => {
                copyButton.innerHTML = '<i class="far fa-copy"></i> Copy';
                copyButton.style.backgroundColor = "";
                copyButton.style.color = "";
                copyButton.style.borderColor = "";
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            showToast("Failed to copy URL");
        });
    });
    
    // 📌 QR Code Generator
    qrButton.addEventListener("click", function() {
        if (qrCodeDiv.innerHTML !== '') {
            qrCodeDiv.innerHTML = '';
            qrButton.innerHTML = '<i class="fas fa-qrcode"></i> Generate QR Code';
            return;
        }
        
        qrButton.innerHTML = '<i class="fas fa-times"></i> Hide QR Code';
        
        if (typeof QRCode !== 'undefined') {
            new QRCode(qrCodeDiv, {
                text: shortUrlLink.href,
                width: 128,
                height: 128,
                colorDark: "#4f46e5",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        } else {
            qrCodeDiv.textContent = "QR Code library not loaded";
        }
    });
    
    // 📌 Helper Functions
    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = "block";
    }
    
    function showShortUrl(url) {
        shortUrlLink.href = url;
        shortUrlLink.textContent = url;
        shortUrlContainer.style.display = "block";
        
        // Clear any existing QR code
        qrCodeDiv.innerHTML = '';
        qrButton.innerHTML = '<i class="fas fa-qrcode"></i> Generate QR Code';
        
        // Scroll to result with animation
        shortUrlContainer.scrollIntoView({ behavior: 'smooth' });
    }
    
    function generateShortCode(length = 6) {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        let code = '';
        for (let i = 0; i < length; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return code;
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    function truncateUrl(url, maxLength = 40) {
        if (url.length <= maxLength) return url;
        return url.substring(0, maxLength) + '...';
    }
    
    // 📌 Update URL Table
    function updateUrlTable() {
        if (savedUrls.length === 0) {
            urlTable.innerHTML = '';
            emptyState.style.display = 'flex';
            return;
        }
        
        emptyState.style.display = 'none';
        urlTable.innerHTML = '';
        
        savedUrls.forEach(url => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>
                    <a href="${url.shortUrl}" target="_blank" class="short-url">
                        ${url.shortUrl}
                    </a>
                </td>
                <td title="${url.longUrl}">${truncateUrl(url.longUrl)}</td>
                <td>${url.clicks}</td>
                <td>${formatDate(url.dateCreated)}</td>
                <td>
                    <button class="btn-action" onclick="copyUrl('${url.shortUrl}')" title="Copy URL">
                        <i class="far fa-copy"></i>
                    </button>
                    <button class="btn-action" onclick="generateQrCode('${url.shortUrl}')" title="Generate QR">
                        <i class="fas fa-qrcode"></i>
                    </button>
                    <button class="btn-action" onclick="deleteUrl(${url.id})" title="Delete">
                        <i class="far fa-trash-alt"></i>
                    </button>
                </td>
            `;
            
            urlTable.appendChild(row);
        });
    }
    
    // 📌 Global functions for table actions
    window.copyUrl = function(url) {
        navigator.clipboard.writeText(url).then(() => {
            showToast("URL copied to clipboard!");
        }).catch(() => {
            showToast("Failed to copy URL");
        });
    };
    
    window.generateQrCode = function(url) {
        // Create modal for QR code
        const modal = document.createElement('div');
        modal.classList.add('qr-modal');
        modal.innerHTML = `
            <div class="qr-modal-content">
                <span class="close-modal">&times;</span>
                <h3>QR Code for your shortened URL</h3>
                <div id="modal-qr-code" class="modal-qr-container"></div>
                <button id="download-qr" class="btn-secondary">
                    <i class="fas fa-download"></i> Download QR Code
                </button>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Generate QR code
        if (typeof QRCode !== 'undefined') {
            new QRCode(document.getElementById('modal-qr-code'), {
                text: url,
                width: 200,
                height: 200,
                colorDark: "#4f46e5",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }
        
        // Close modal functionality
        modal.querySelector('.close-modal').addEventListener('click', function() {
            document.body.removeChild(modal);
        });
        
        // Download QR code
        document.getElementById('download-qr').addEventListener('click', function() {
            const img = document.querySelector('#modal-qr-code img');
            if (img) {
                const link = document.createElement('a');
                link.download = 'qrcode.png';
                link.href = img.src;
                link.click();
            }
        });
        
        // Add modal styles if not already in the document
        if (!document.getElementById('modal-styles')) {
            const style = document.createElement('style');
            style.id = 'modal-styles';
            style.textContent = `
                .qr-modal {
                    display: flex;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.5);
                    align-items: center;
                    justify-content: center;
                }
                .qr-modal-content {
                    background-color: white;
                    padding: 25px;
                    border-radius: 8px;
                    width: 90%;
                    max-width: 350px;
                    text-align: center;
                }
                .close-modal {
                    float: right;
                    font-size: 24px;
                    font-weight: bold;
                    cursor: pointer;
                }
                .modal-qr-container {
                    margin: 20px 0;
                    display: flex;
                    justify-content: center;
                }
                #download-qr {
                    margin-top: 15px;
                }
            `;
            document.head.appendChild(style);
        }
    };
    
    window.deleteUrl = function(id) {
        if (confirm("Are you sure you want to delete this shortened URL?")) {
            savedUrls = savedUrls.filter(url => url.id !== id);
            localStorage.setItem("tinylink_urls", JSON.stringify(savedUrls));
            updateUrlTable();
            showToast("URL deleted successfully");
        }
    };
    
    // 📌 Toast notification
    function showToast(message) {
        // Create toast element if it doesn't exist
        let toast = document.getElementById('toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background-color: var(--dark);
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                font-size: 14px;
                box-shadow: 0 3px 10px rgba(0,0,0,0.2);
                z-index: 1000;
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.3s ease;
            `;
            document.body.appendChild(toast);
        }
        
        toast.textContent = message;
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
        
        setTimeout(() => {
            toast.style.transform = 'translateY(100px)';
            toast.style.opacity = '0';
        }, 3000);
    }
    
    // 📌 Track URL clicks
    async function trackUrlClick(shortCode) {
        try {
            await fetch(`http://127.0.0.1:8000/api/track/${shortCode}`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute("content")
                }
            });
        } catch (error) {
            console.error("Error tracking click:", error);
        }
    }
    
    // Check for redirect parameters
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get('code') || window.location.pathname.split('/s/')[1];
    
    if (code) {
        const urlData = savedUrls.find(url => url.shortCode === code);
        if (urlData) {
            // Increment click count locally
            urlData.clicks++;
            localStorage.setItem("tinylink_urls", JSON.stringify(savedUrls));
            
            // Track click on server (if available)
            trackUrlClick(code);
            
            // Redirect to original URL
            window.location.href = urlData.longUrl;
        }
    }
    
    // Add improved error handling for API requests
    window.addEventListener('error', function(event) {
        if (event.message.includes('fetch') || event.message.includes('API') || 
            event.message.includes('network')) {
            showToast("Network error. Check your connection and try again.");
        }
    });

    // In your script.js file
    fetch('/shorten', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ url: urlToShorten })
    });
});