// SRMS Enhanced JavaScript - Modern Features
class SRMS {
    constructor() {
        this.init();
    }

    init() {
        this.initPWA();
        this.initThemeSystem();
        this.initKeyboardShortcuts();
        this.initAdvancedSearch();
        this.initNotifications();
        this.initOfflineSupport();
    }

    // PWA Initialization
    initPWA() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('SW registered:', registration);
                })
                .catch(error => {
                    console.log('SW registration failed:', error);
                });
        }

        // Add to home screen prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            this.showInstallButton();
        });
    }

    showInstallButton() {
        const installBtn = document.createElement('button');
        installBtn.className = 'btn btn-primary install-btn';
        installBtn.innerHTML = '<i class="fas fa-download"></i> Install App';
        installBtn.onclick = () => {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                }
                deferredPrompt = null;
                installBtn.remove();
            });
        };
        document.body.appendChild(installBtn);
    }

    // Theme System
    initThemeSystem() {
        // Create theme toggle button
        const themeToggle = document.createElement('button');
        themeToggle.className = 'theme-toggle';
        themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        themeToggle.onclick = () => this.toggleTheme();
        document.body.appendChild(themeToggle);

        // Load saved theme
        const savedTheme = localStorage.getItem('srms-theme') || 'light';
        this.setTheme(savedTheme);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('srms-theme', theme);
        
        const themeIcon = document.querySelector('.theme-toggle i');
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    // Keyboard Shortcuts
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'k':
                        e.preventDefault();
                        this.focusSearch();
                        break;
                    case 'n':
                        e.preventDefault();
                        this.openNewRecord();
                        break;
                    case 'd':
                        e.preventDefault();
                        this.toggleTheme();
                        break;
                }
            }
        });
    }

    focusSearch() {
        const searchInput = document.querySelector('.global-search');
        if (searchInput) {
            searchInput.focus();
        }
    }

    openNewRecord() {
        const addButton = document.querySelector('.btn-add-new');
        if (addButton) {
            addButton.click();
        }
    }

    // Advanced Search
    initAdvancedSearch() {
        this.createGlobalSearch();
    }

    createGlobalSearch() {
        // Only add search to dashboard pages, not index
        if (window.location.pathname.includes('dashboard') || window.location.pathname.includes('manage')) {
            const searchContainer = document.createElement('div');
            searchContainer.className = 'global-search-container';
            searchContainer.innerHTML = `
                <div class="search-wrapper">
                    <input type="text" class="global-search" placeholder="Search students, teachers, classes... (Ctrl+K)">
                    <i class="fas fa-search search-icon"></i>
                    <div class="search-results"></div>
                </div>
            `;
            
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                navbar.appendChild(searchContainer);
            }
        }

        const searchInput = searchContainer.querySelector('.global-search');
        searchInput.addEventListener('input', (e) => {
            this.performGlobalSearch(e.target.value);
        });
    }

    async performGlobalSearch(query) {
        if (query.length < 2) {
            this.hideSearchResults();
            return;
        }

        try {
            const response = await fetch('/api/global-search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ query })
            });
            
            const results = await response.json();
            this.displaySearchResults(results);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(results) {
        const resultsContainer = document.querySelector('.search-results');
        if (!results || results.length === 0) {
            resultsContainer.innerHTML = '<div class="no-results">No results found</div>';
            return;
        }

        const html = results.map(result => `
            <div class="search-result-item" onclick="window.location.href='${result.url}'">
                <div class="result-type">${result.type}</div>
                <div class="result-title">${result.title}</div>
                <div class="result-subtitle">${result.subtitle}</div>
            </div>
        `).join('');
        
        resultsContainer.innerHTML = html;
        resultsContainer.style.display = 'block';
    }

    hideSearchResults() {
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }

    // Notifications System
    initNotifications() {
        this.createNotificationContainer();
    }

    createNotificationContainer() {
        const container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }

    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.querySelector('.notification-container').appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, duration);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    // Offline Support
    initOfflineSupport() {
        window.addEventListener('online', () => {
            this.showNotification('Connection restored', 'success');
            this.syncOfflineData();
        });

        window.addEventListener('offline', () => {
            this.showNotification('You are now offline', 'warning');
        });
    }

    syncOfflineData() {
        const offlineData = JSON.parse(localStorage.getItem('offlineData') || '[]');
        if (offlineData.length > 0) {
            // Sync offline data when connection is restored
            fetch('/api/sync-offline-data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(offlineData)
            }).then(() => {
                localStorage.removeItem('offlineData');
                this.showNotification('Offline data synced successfully', 'success');
            });
        }
    }

    // Utility Functions
    static formatDate(date) {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }).format(new Date(date));
    }

    static formatNumber(number, decimals = 2) {
        return Number(number).toFixed(decimals);
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize SRMS when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.srms = new SRMS();
});

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SRMS;
}