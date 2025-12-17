const API_CONFIGS = {
    '123pan': './api/123pan.php',
    'lanzou': './api/lanzou.php',
    'feijipan': 'https://jx.fsapk.top/json/parser',
    'ilanzou': 'https://jx.fsapk.top/json/parser'
};

const LINK_TYPE_NAMES = {
    '123pan': '123云盘',
    'lanzou': '蓝奏云',
    'feijipan': '小飞机网盘',
    'ilanzou': '蓝奏云优享版'
};

//123云盘账号配置
const PAN123_CONFIG = {
    username: "请修改为您的123云盘账号",
    password: "请修改为您的123云盘密码"
};


let currentDirectLink = '';
let currentFileName = '';
let selectedFormat = 'json';

const CONTACTS = {
    qq: '10086',
    github: 'https://github.com/',
    email: '10086@10086',
    telegram: 'https://t.me/'
};

const GREETINGS = {
    morning: {
        icon: '🌅',
        text: '早上好！',
        subtitle: '愿您有美好的一天',
        lucideIcon: 'sun',
        class: 'morning'
    },
    afternoon: {
        icon: '☀️',
        text: '下午好！',
        subtitle: '午后时光，继续加油',
        lucideIcon: 'sun',
        class: 'afternoon'
    },
    evening: {
        icon: '🌆',
        text: '傍晚好！',
        subtitle: '夕阳西下，美好黄昏',
        lucideIcon: 'sunset',
        class: 'evening'
    },
    night: {
        icon: '🌙',
        text: '晚上好！',
        subtitle: '夜深了，注意休息',
        lucideIcon: 'moon',
        class: 'night'
    }
};

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.format-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.format-option').forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            selectedFormat = this.dataset.format;
        });
    });

    const textFormatOption = document.querySelector('[data-format="text"]');
    if (textFormatOption) {
        textFormatOption.style.display = 'none';
    }
    
    const jsonFormatOption = document.querySelector('[data-format="json"]');
    if (jsonFormatOption) {
        jsonFormatOption.classList.add('active');
    }

    document.getElementById('url').addEventListener('input', function(e) {
        detectLinkType(e.target.value);
    });

    updateGreeting();
    setInterval(updateGreeting, 60000);
});

function detectLinkType(url) {
    const linkTypeInput = document.getElementById('linkType');
    const urlLower = url.toLowerCase();
    
    if (urlLower.includes('123pan.com') || urlLower.includes('123865.com') || urlLower.includes('123684.com')) {
        linkTypeInput.value = '123pan';
    } else if (urlLower.includes('lanzou') && !urlLower.includes('ilanzou')) {
        linkTypeInput.value = 'lanzou';
    } else if (urlLower.includes('feijipan')) {
        linkTypeInput.value = 'feijipan';
    } else if (urlLower.includes('ilanzou')) {
        linkTypeInput.value = 'ilanzou';
    } else {
        linkTypeInput.value = '';
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}

function openContact(type) {
    switch(type) {
        case 'qq':
            showToast(`QQ号: ${CONTACTS.qq}`, 'success');
            navigator.clipboard.writeText(CONTACTS.qq).catch(() => {});
            break;
        case 'github':
            window.open(CONTACTS.github, '_blank');
            break;
        case 'email':
            window.location.href = `mailto:${CONTACTS.email}`;
            break;
        case 'telegram':
            window.open(CONTACTS.telegram, '_blank');
            break;
    }
}

function updateGreeting() {
    const now = new Date();
    const hour = now.getHours();
    
    let greetingType;
    if (hour >= 5 && hour < 12) {
        greetingType = 'morning';
    } else if (hour >= 12 && hour < 17) {
        greetingType = 'afternoon';
    } else if (hour >= 17 && hour < 21) {
        greetingType = 'evening';
    } else {
        greetingType = 'night';
    }
    
    const greeting = GREETINGS[greetingType];
    const greetingCard = document.getElementById('greetingCard');
    const greetingTitle = document.getElementById('greetingTitle');
    const greetingIcon = document.getElementById('greetingIcon');
    const greetingText = document.getElementById('greetingText');
    const greetingTime = document.getElementById('greetingTime');
    
    greetingCard.classList.remove('morning', 'afternoon', 'evening', 'night');
    greetingCard.classList.add(greeting.class);
    
    greetingTitle.innerHTML = `<svg data-lucide="${greeting.lucideIcon}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg><span>早晚问候</span>`;
    greetingIcon.textContent = greeting.icon;
    greetingText.textContent = greeting.text;
    greetingTime.textContent = greeting.subtitle;
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const iconSvg = type === 'success' 
        ? '<svg data-lucide="check-circle" class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>'
        : '<svg data-lucide="x-circle" class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>';
    
    toast.innerHTML = `
        <div class="toast-content">
            ${iconSvg}
            <span class="toast-text">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

function updateStatus(text, type = 'info') {
    const statusText = document.getElementById('statusText');
    statusText.textContent = text;
}

function getLinkTypeName(linkType) {
    return LINK_TYPE_NAMES[linkType] || linkType;
}

function extractDownloadLink(result) {
    let downloadLink = '';
    let fileName = '';
    
    try {
        const jsonResult = typeof result === 'string' ? JSON.parse(result) : result;
        
        console.log('提取下载链接，原始数据:', jsonResult);
        
        if (jsonResult && jsonResult.data) {
            if (jsonResult.data.down) {
                downloadLink = jsonResult.data.down;
            }
            else if (jsonResult.data.url) {
                downloadLink = jsonResult.data.url;
            }
            else if (jsonResult.data.directLink) {
                downloadLink = jsonResult.data.directLink;
            }
            else if (jsonResult.data.download_url) {
                downloadLink = jsonResult.data.download_url;
            }
            
            if (jsonResult.data.name) {
                fileName = jsonResult.data.name;
            } else if (jsonResult.data.filename) {
                fileName = jsonResult.data.filename;
            } else if (jsonResult.data.file_name) {
                fileName = jsonResult.data.file_name;
            }
        }
        
        if (!downloadLink && jsonResult.download_url) {
            downloadLink = jsonResult.download_url;
        }
        if (!downloadLink && jsonResult.directLink) {
            downloadLink = jsonResult.directLink;
        }
        if (!fileName && jsonResult.file_name) {
            fileName = jsonResult.file_name;
        }
        
        console.log('提取结果 - 下载链接:', downloadLink, '文件名:', fileName);
        
    } catch (e) {
        console.error('解析下载链接失败:', e);
    }
    
    return { downloadLink, fileName };
}

document.getElementById('parseForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = formData.get('url');
    const pwd = formData.get('pwd');
    const linkType = formData.get('linkType');
    
    console.log('表单提交:', { url, pwd, linkType, selectedFormat });
    
    const detectedType = linkType || detectLinkTypeFromUrl(url);
    
    if (!detectedType) {
        showToast('不支持的链接类型，请检查链接格式', 'error');
        return;
    }
    
    const apiUrl = API_CONFIGS[detectedType];
    if (!apiUrl) {
        showToast('不支持的链接类型', 'error');
        return;
    }
    
    document.getElementById('loading').classList.add('show');
    document.getElementById('resultSection').classList.remove('show');
    const linkTypeName = getLinkTypeName(detectedType);
    updateStatus(`正在解析 ${linkTypeName} 链接...`, 'info');
    
    try {
        let requestUrl;
        
        if (detectedType === 'feijipan' || detectedType === 'ilanzou') {
            requestUrl = `${apiUrl}?url=${encodeURIComponent(url)}`;
            if (pwd) {
                requestUrl += `&pwd=${encodeURIComponent(pwd)}`;
            }
        } else if (detectedType === '123pan') {
            requestUrl = `${apiUrl}?user=${encodeURIComponent(PAN123_CONFIG.username)}&pass=${encodeURIComponent(PAN123_CONFIG.password)}&url=${encodeURIComponent(url)}`;
            if (pwd) {
                requestUrl += `&pwd=${encodeURIComponent(pwd)}`;
            }
        } else {
            requestUrl = `${apiUrl}?url=${encodeURIComponent(url)}`;
            if (pwd) {
                requestUrl += `&pwd=${encodeURIComponent(pwd)}`;
            }
            if (selectedFormat) {
                requestUrl += `&type=${selectedFormat}`;
            }
        }
        
        console.log('最终请求URL:', requestUrl);
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);
        
        const response = await fetch(requestUrl, {
            signal: controller.signal,
            method: 'GET',
            headers: {
                'Accept': 'application/json, text/plain, */*',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            mode: 'cors',
            credentials: detectedType === 'feijipan' || detectedType === 'ilanzou' ? 'omit' : 'same-origin'
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP错误: ${response.status} ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        let result;
        
        if (contentType && contentType.includes('application/json')) {
            result = await response.json();
        } else {
            const textResult = await response.text();
            console.log('原始响应文本:', textResult);
            try {
                result = JSON.parse(textResult);
            } catch (e) {
                result = textResult;
            }
        }
        
        console.log('API返回结果:', result);
        
        document.getElementById('loading').classList.remove('show');
        const { downloadLink, fileName } = extractDownloadLink(result);
        currentDirectLink = downloadLink;
        currentFileName = fileName;        
        const resultSection = document.getElementById('resultSection');
        const resultHeader = document.getElementById('resultHeader');
        const resultContent = document.getElementById('resultContent');
        const downloadBtn = document.getElementById('downloadBtn');
        const copyBtn = document.getElementById('copyBtn');
        
        resultSection.className = 'result-section show';
        
        const isSuccess = result && (
            result.code === 200 || 
            result.zt === 1 || 
            result.message === 'success' ||
            result.success === true ||
            result.status === 'success' ||
            (result.data && (result.data.download_url || result.data.directLink || result.data.url || result.data.down))
        );
        
        if (isSuccess) {
            resultHeader.className = 'result-header';
            resultHeader.innerHTML = '<svg data-lucide="check-circle" class="result-header-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg><span class="result-title">解析完成</span>';
            
            if (currentDirectLink) {
                showToast('解析成功！已找到下载链接', 'success');
                downloadBtn.style.display = 'inline-flex';
                copyBtn.style.display = 'inline-flex';
            } else {
                showToast('解析成功！', 'success');
                downloadBtn.style.display = 'none';
                copyBtn.style.display = 'inline-flex';
            }
        } else {
            resultHeader.className = 'result-header error';
            resultHeader.innerHTML = '<svg data-lucide="x-circle" class="result-header-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg><span class="result-title">解析失败</span>';
            showToast(result?.msg || result?.inf || result?.message || result?.error || '解析失败', 'error');
            
            downloadBtn.style.display = 'none';
            copyBtn.style.display = 'none';
        }
        
        resultContent.innerHTML = `<pre class="json-result">${JSON.stringify(result, null, 2)}</pre>`;
        
    } catch (error) {
        document.getElementById('loading').classList.remove('show');
        const resultSection = document.getElementById('resultSection');
        const resultHeader = document.getElementById('resultHeader');
        const resultContent = document.getElementById('resultContent');
        const downloadBtn = document.getElementById('downloadBtn');
        const copyBtn = document.getElementById('copyBtn');
        
        resultSection.className = 'result-section show';
        resultHeader.className = 'result-header error';
        resultHeader.innerHTML = '<svg data-lucide="x-circle" class="result-header-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg><span class="result-title">解析失败</span>';
        
        let errorMessage = error.message;
        if (error.name === 'AbortError') {
            errorMessage = '请求超时，请重试';
        } else if (error.message.includes('Failed to fetch')) {
            errorMessage = '无法连接到服务器，请检查网络连接或API接口是否可用';
        }
        
        resultContent.textContent = `解析失败: ${errorMessage}\n\n请检查：\n1. API接口是否可用\n2. 网络连接是否正常\n3. 链接格式是否正确`;
        
        downloadBtn.style.display = 'none';
        copyBtn.style.display = 'none';
        currentDirectLink = '';
        currentFileName = '';
        
        showToast('解析失败: ' + errorMessage, 'error');
        
        console.error('解析错误详情:', error);
    }
});

function detectLinkTypeFromUrl(url) {
    const urlLower = url.toLowerCase();
    
    if (urlLower.includes('123pan.com') || urlLower.includes('123865.com') || urlLower.includes('123684.com')) {
        return '123pan';
    } else if (urlLower.includes('lanzou') && !urlLower.includes('ilanzou')) {
        return 'lanzou';
    } else if (urlLower.includes('feijipan')) {
        return 'feijipan';
    } else if (urlLower.includes('ilanzou')) {
        return 'ilanzou';
    }
    
    return null;
}

function clearForm() {
    document.getElementById('parseForm').reset();
    document.getElementById('resultSection').classList.remove('show');
    document.getElementById('loading').classList.remove('show');
    document.getElementById('linkType').value = '';
    currentDirectLink = '';
    currentFileName = '';
    
    document.querySelectorAll('.format-option').forEach(opt => opt.classList.remove('active'));
    const jsonFormatOption = document.querySelector('[data-format="json"]');
    if (jsonFormatOption) {
        jsonFormatOption.classList.add('active');
    }
    selectedFormat = 'json';
}

function downloadFile() {
    if (!currentDirectLink) {
        showToast('未找到有效的下载链接', 'error');
        return;
    }
    
    const fileName = currentFileName || '文件';
    const confirmMessage = `确定要下载文件吗？\n\n文件名: ${fileName}\n\n点击"确定"将在新窗口打开下载链接`;
    
    if (confirm(confirmMessage)) {
        window.open(currentDirectLink, '_blank');
        showToast('已打开下载链接');
    }
}

function copyDirectLink() {
    let textToCopy = currentDirectLink;
    
    if (!textToCopy) {
        try {
            const resultContent = document.getElementById('resultContent');
            const resultText = resultContent.textContent;
            const resultJson = JSON.parse(resultText);
            
            if (resultJson.data && resultJson.data.down) {
                textToCopy = resultJson.data.down;
            }
            else if (resultJson.data && resultJson.data.url) {
                textToCopy = resultJson.data.url;
            }
            else if (resultJson.data && resultJson.data.directLink) {
                textToCopy = resultJson.data.directLink;
            }
            else if (resultJson.data && resultJson.data.download_url) {
                textToCopy = resultJson.data.download_url;
            }
            else if (resultJson.download_url) {
                textToCopy = resultJson.download_url;
            }
            else if (resultJson.directLink) {
                textToCopy = resultJson.directLink;
            }
        } catch (e) {
            console.error('从结果中提取链接失败:', e);
        }
    }
    
    if (!textToCopy) {
        showToast('没有可复制的链接', 'error');
        return;
    }
    
    navigator.clipboard.writeText(textToCopy).then(() => {
        const copyBtn = document.getElementById('copyBtn');
        const originalHTML = copyBtn.innerHTML;
        
        copyBtn.innerHTML = '<svg data-lucide="check" class="action-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>已复制';
        copyBtn.classList.add('copied');
        
        setTimeout(() => {
            copyBtn.innerHTML = originalHTML;
            copyBtn.classList.remove('copied');
        }, 2000);
        
        showToast('链接已复制到剪贴板');
    }).catch(() => {
        const textArea = document.createElement('textarea');
        textArea.value = textToCopy;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showToast('链接已复制到剪贴板');
        } catch (err) {
            showToast('复制失败，请手动复制', 'error');
        }
        document.body.removeChild(textArea);
    });
}