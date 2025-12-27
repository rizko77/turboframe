/*
 * turbo-lite.js — lightweight client-side navigation fallback
 * Optimized for Desktop Speed and Ultra Mobile Performance
 */
(function (){
    'use strict';

    // --- Konstanta dan Utilitas ---
    const MAIN_CONTENT_ID = 'main-content';
    const USE_RIC = typeof window.requestIdleCallback === 'function';

    function isSameOrigin(url) {
        try {
            const u = new URL(url, location.href);
            return u.origin === location.origin;
        } catch (e) { return false; }
    }

    function shouldHandleLink(a) {
        if (!a || !a.href) return false;
        if (a.hash && a.pathname === location.pathname && a.hash !== '#') return false; 
        if (a.protocol.indexOf('http') !== 0) return false;
        if (a.target && a.target !== '' && a.target !== '_self') return false;
        if (a.hasAttribute('download')) return false;
        if (a.getAttribute('rel') === 'external' || a.getAttribute('rel') === 'nofollow' || a.getAttribute('data-turbo-lite') === 'false' || a.hasAttribute('data-no-prefetch') || a.hasAttribute('data-turbo-lite-ignore')) return false; 
        
        return isSameOrigin(a.href);
    }
    
   
    function executeScriptsAsync(scripts, container) {
        return new Promise(resolve => {
            const scriptsToLoad = scripts.slice(); 
            
            function scheduleNext(callback) {
                if (USE_RIC) {
                    window.requestIdleCallback(callback, { timeout: 50 }); 
                } else {
                    window.requestAnimationFrame(() => setTimeout(callback, 0)); 
                }
            }
            
            function loadNext() {
                if (scriptsToLoad.length === 0) {
                    return resolve();
                }

                const s = scriptsToLoad.shift();
                
                const type = s.getAttribute('type');
                if (type && type !== 'application/javascript' && type !== 'text/javascript') {
                     loadNext();
                     return;
                }

                if (s.src && document.querySelector(`script[src="${s.src}"][data-turbo-once]`)) {
                    loadNext();
                    return; 
                }
                scheduleNext(() => {
                    const script = document.createElement('script');
                    
                    
                    for (const attr of s.attributes) {
                        script.setAttribute(attr.name, attr.value);
                    }
                    
                    if (s.src) {
                        script.onload = loadNext;
                        script.onerror = loadNext;
                        script.async = s.hasAttribute('async') ? s.async : false; 
                        script.src = s.src;
                    } else {
                        script.textContent = s.textContent;
                    }
                    
                    s.remove(); 
                    document.body.appendChild(script); 
                    
                    if (!s.src) {
                        loadNext(); 
                    }
                });
            }
            loadNext();
        });
    }

    // --- Core Navigation Logic ---

    async function fetchAndReplace(url, addToHistory=true) {
        let timer = null;
        let originalScroll = window.scrollY; 

        try {
            // 1. Start Navigation Indicator
            document.documentElement.classList.add('turbo-navigating');
            timer = setTimeout(() => {
                document.documentElement.classList.add('turbo-loading');
            }, 50); 
            
            // 2. Fetch HTML
            const fetchOptions = { 
                credentials: 'same-origin', 
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            };
            
            const res = await fetch(url, fetchOptions);
            if (!res.ok) { location.href = url; return; }
            const text = await res.text();
            
            // 3. DOM Parsing (Paling Cepat)
            const parser = new DOMParser();
            const doc = parser.parseFromString(text, 'text/html'); 

            const newTitle = doc.querySelector('title');
            if (newTitle) document.title = newTitle.textContent;

            const newMainContent = doc.getElementById(MAIN_CONTENT_ID);
            const currentMainContent = document.getElementById(MAIN_CONTENT_ID);
            
            if (!newMainContent || !currentMainContent) { 
                console.error(`Missing main content container: #${MAIN_CONTENT_ID}. Fallback to full load.`);
                location.href = url; return; 
            }
            
            
            const scriptsToExecute = Array.from(newMainContent.querySelectorAll('script:not([data-turbo-once])'));

            
            const currentHead = document.head;
            const newHead = doc.head;
            
            
            const newHeadElements = newHead.querySelectorAll('link[rel="stylesheet"], link[rel="canonical"], meta:not([charset]), title');
            const currentHeadElements = currentHead.querySelectorAll('link[rel="stylesheet"], link[rel="canonical"], meta:not([charset]), title');

            currentHeadElements.forEach(el => el.remove());
            newHeadElements.forEach(el => currentHead.appendChild(el));

            // 4. Ganti Konten Utama dengan InnerHTML untuk kecepatan maksimum
            currentMainContent.innerHTML = newMainContent.innerHTML;
            
            // 5. History Management
            if (addToHistory) {
                
                history.pushState({ turbo: true, url: url, scrollY: 0 }, document.title, url);
            } else if (history.state && history.state.turbo) {
                 
                 window.history.replaceState({ turbo: true, url: url, scrollY: originalScroll }, document.title, url);
            }


            // 6. Scroll to Top (Jika bukan popstate/back)
            if (addToHistory) {
                 window.scrollTo(0,0);
            } else {
                 
                 const scrollY = history.state && history.state.scrollY || 0;
                 window.scrollTo(0, scrollY);
            }
            
            // 7. Smart Script Re-execution (Dibuat Asinkron dengan requestIdleCallback)
            await executeScriptsAsync(scriptsToExecute, currentMainContent);
            
            // 8. Trigger Event (Opsional: untuk integrasi library lain)
            document.dispatchEvent(new CustomEvent('turbo:load', { bubbles: true }));

        } catch (err) {
            console.error('turbo-lite error, falling back to full load', err);
            location.href = url;
        } finally {
            // 9. End Navigation Indicator
            if (timer) clearTimeout(timer);
            document.documentElement.classList.remove('turbo-navigating');
            document.documentElement.classList.remove('turbo-loading');
            
            document.documentElement.classList.add('is-complete');
            setTimeout(() => {
                document.documentElement.classList.remove('is-complete');
            }, 300); 
        }
    }

    // --- Event Handlers dan Prefetch (Optimasi Mobile) ---

    document.addEventListener('click', function (e) {
        const a = e.target.closest && e.target.closest('a');
        if (!a) return;
        if (!shouldHandleLink(a)) return;
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        
        e.preventDefault();
        fetchAndReplace(a.href, true);
    }, true);

    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.turbo) { 
            fetchAndReplace(location.href, false);
        }
    });

    // Prefetch on Hover (Desktop) and Touchstart (Mobile)
    (function (){
        const seen = new Set();
        let timer = null;
        let activePrefetch = null; 

        const prefetchHandler = function (e) {
            // Cari tag <a> yang valid
            const target = e.type === 'touchstart' ? e.target : e.target.closest('a');
            const a = target && target.closest('a');

            if (!a || !shouldHandleLink(a)) return;
            const href = a.href;
            if (seen.has(href) || activePrefetch === href) return;

            const delay = e.type === 'touchstart' ? 0 : 60; 

            if (timer) clearTimeout(timer);
            
            timer = setTimeout(()=>{
                if (activePrefetch === href) return;
                activePrefetch = href;

                const request = new Request(href, { 
                    method: 'GET', 
                    credentials: 'same-origin', 
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                    priority: 'low' 
                });

                fetch(request)
                .then(res => { 
                    if (res.ok) seen.add(href);
                    if (activePrefetch === href) activePrefetch = null;
                 })
                .catch(err => {
                    console.error('Prefetch failed:', err);
                    if (activePrefetch === href) activePrefetch = null;
                });
                
            }, delay);
        };
        
        document.addEventListener('touchstart', prefetchHandler, { passive: true });
        document.addEventListener('mouseover', prefetchHandler, { passive: true });
        
        document.addEventListener('touchend', function () { 
             if (timer) { clearTimeout(timer); timer = null; } 
        }, { passive: true });
        
        document.addEventListener('mouseout', function () {
             if (timer) { clearTimeout(timer); timer = null; } 
        }, { passive: true });
        
    })();

    window.__turbo_lite = { fetchAndReplace };

})();