(function (window, document) {
  "use strict";
  let lastSlideIndex = 0;

  // ----------------------------
  // Konfiguration & Defaults
  // ----------------------------
  const DEFAULTS = {
    galleryKey: 'slfs',
    pro: false,
    scanBody: true, // 
    settingsUrl: '',
  };

  const CFG = Object.assign({}, DEFAULTS, SLFS_CONFIG || {});
  const GALLERY_KEY = String(CFG.galleryKey);

  const IMG_EXT = /\.(jpe?g|png|gif|webp|avif|bmp|svg)(\?.*)?$/i;
  const VID_EXT = /\.(mp4|webm|ogv|ogg)(\?.*)?$/i;
  let elements = {};
  let iframes = null;
  let videos = null;
  let NElements = 0;
  let NIframes = 0;
  let NVideos = 0;

  // Set to track processed image URLs  
  //const processedImageUrls = new Set(); 

  // ----------- SETTINGS LOADER -----------------
  function loadSettings() {
    CFG.scanBody === true;
    return {
        cssClassesToSearch: CFG.cssClassesToSearch,
        scanBody: CFG.scanBody === true
      };
  }

  // --------- Collect DOM-Elements and FS-Lightbox-Links ---------
  function collectAndWire(settings) {  
    const classes = Array.isArray(settings.cssClassesToSearch) && settings.cssClassesToSearch.length  
      ? settings.cssClassesToSearch : DEFAULTS.cssClassesToSearch;  
    
    elements.forEach(element => {  
        const figure = element.closest('figure');
        if (figure && classes.some(cssClass => figure.classList.contains(cssClass))) {  
            if (element.tagName === 'IMG') {
                processImage(element);  
            } else if (element.tagName === 'VIDEO') {
                processHTML5Video(element);  
            } else if (element.tagName === 'IFRAME') {
                processYouTubeIframe(element);  
            }  
        }  
    });  
  
    if (typeof window.refreshFsLightbox === 'function') {  
        window.refreshFsLightbox();  
    }  
  }

  // ----------- IMAGE HTML Coder Generator ------------------
  // Helpers: Dateiendungen, Linktypen
  // ----------------------------
  function isMediaUrl(href) {
    return IMG_EXT.test(href || '') || VID_EXT.test(href || '');
  }

  function bestImageHref(img) {  
    // 1) Versuch: vorhandener Anker im Figure  
    const figure = img.closest('figure');  
    if (figure) {  
        const a = figure.querySelector('a[href]');  
        if (a && isMediaUrl(a.getAttribute('href'))) return a.getAttribute('href');  
    }  
  
    // 2) srcset: Suche nach dem Bild mit maximaler Auflösung  
    const srcset = img.getAttribute('srcset');  
    if (srcset) {  
        const sources = srcset.split(',').map(src => {  
            const [url, size] = src.trim().split(' ');  
            return { url, size: parseInt(size, 10) || 0 };  
        });  
  
        const maxSource = sources.reduce((max, current) => current.size > max.size ? current : max, { size: 0 });  
        if (maxSource.url) return maxSource.url;  
    }  
  
    // 3) Fallback: aktuelles src  
    if (img.currentSrc) return img.currentSrc;  
    if (img.src) return img.src;  
  
    return null;  
  }  

  function processImage(img) {
    // existierender Link?
    const a = img.closest('a');
    
    if (a) {
      // explizit ausgeschlossen
      if (a.classList.contains('wp-lightbox')) return;
      const href = a.getAttribute('href');
      //if (!isMediaUrl(href) || processedImageUrls.has(href)) return; // z.B. Link zu einem Post/Seite
      if (!isMediaUrl(href)) return; // z.B. Link zu einem Post/Seite
      a.setAttribute('data-fslightbox', GALLERY_KEY); // FsLightbox übernimmt
      //rocessedImageUrls.add(href);
      return;
    }

    // kein Link: wir bauen einen
    const href = bestImageHref(img);
    //if (!href || processedImageUrls.has(href)) return;
    if (!href) return;
    const wrapper = document.createElement('a');
    wrapper.setAttribute('data-fslightbox', GALLERY_KEY);
    wrapper.setAttribute('href', href);
    wrapper.setAttribute('data-type', 'image');
    wrapper.setAttribute('aria-label', 'Open fullscreen lightbox with current image');
    img.replaceWith(wrapper);
    wrapper.appendChild(img);
    //processedImageUrls.add(href); 
  }

  // ------------ VIDEO HTML Code Generator----------------
  // Video-Steuerung (YT+HTML5)
  // ----------------------------
  function adoptYTIframeLinks() {
    //const iframes = document.querySelectorAll('iframe');
    iframes = getIFrames();

    for (const iframe of iframes) {
        if (iframe.src.includes('youtube.com') || iframe.src.includes('youtube-nocookie.com')) {
            iframe.src = normalizeYouTubeIframeUrl(iframe.src);
        }
    }
  }
  /**
 * Normalisiert YouTube-URLs für IFrames und hängt enablejsapi=1 korrekt an.
 * - schaltet auf www.youtube-nocookie.com (Privacy-Enhanced Mode)
 * - wandelt watch-/youtu.be-Links in /embed/VIDEO_ID um
 * - behält bestehende Query-Parameter bei und setzt enablejsapi=1
 * @param {string} rawUrl
 * @returns {string} gereinigte URL
 */
  function normalizeYouTubeIframeUrl(rawUrl) {
    // Ungültige Eingaben überspringen
    if (!rawUrl || typeof rawUrl !== 'string') return rawUrl;

    let url;
    try {
      url = new URL(rawUrl, window.location.href);
    } catch (_) {
      return rawUrl; // nicht parsebar, nichts erzwingen
    }

    // Video-ID extrahieren (aus /embed/, watch?v=, oder youtu.be/)
    let videoId = null;

    // /embed/VIDEO_ID
    const embedMatch = url.pathname.match(/\/embed\/([^/?#]+)/i);
    if (embedMatch) {
      videoId = embedMatch[1];
    }

    // watch?v=VIDEO_ID
    if (!videoId && url.searchParams.has('v')) {
      videoId = url.searchParams.get('v');
    }

    // youtu.be/VIDEO_ID
    if (!videoId && /(^|\.)youtu\.be$/i.test(url.hostname)) {
      const parts = url.pathname.split('/').filter(Boolean);
      if (parts.length) videoId = parts[0];
    }

    // Auf Embed-Form bringen, wenn wir eine ID haben
    if (videoId) {
      url.pathname = `/embed/${videoId}`;
      // Der zusätzliche watch-Parameter 'v' ist dann überflüssig
      url.searchParams.delete('v');
    }

    // Privacy-Enhanced Mode: korrekten Host setzen (kein String-Replacement!)
    const isYouTubeHost =
      /(^|\.)youtube\.com$/i.test(url.hostname) ||
      /(^|\.)youtube-nocookie\.com$/i.test(url.hostname) ||
      /(^|\.)youtu\.be$/i.test(url.hostname);

    if (isYouTubeHost) {
      url.hostname = 'www.youtube-nocookie.com';
    }

    // enablejsapi=1 anhängen (falls nicht vorhanden)
    if (!url.searchParams.has('enablejsapi')) {
      url.searchParams.set('enablejsapi', '1');
    }

    // Ergebnis
    return url.toString();
  }
  function processHTML5Video(video) {
    const anchorParent = video.closest('a');
    if (anchorParent && anchorParent.classList.contains('wp-lightbox')) return;

    const src = video.currentSrc || video.getAttribute('src');
    if (!src) return;

    const parent = video.parentElement;
    const a = makeFsAnchor(src, { 'data-type': 'video' });
    if (video.poster) a.setAttribute('data-video-poster', video.poster);
    parent.appendChild(a);

    // Overlay-Button zum Öffnen
    ensureWrapperWithButton(video, '▶', () => a.click());
  }
  function processYouTubeIframe(iframe) {
    const watch = toYouTubeWatchUrl(iframe.src);
    if (!watch) return;

    const parent = iframe.parentElement;
    const a = makeFsAnchor(watch, { 'data-type': 'youtube' });
    parent.appendChild(a);

    // roter Button links oben
    ensureWrapperWithButton(iframe, '▶', () => a.click());
  }
  function toYouTubeWatchUrl(raw) {
    if (!raw) return null;
    try {
      const url = new URL(raw, window.location.href);
      const host = url.hostname;
      // /embed/VIDEOID -> watch?v=VIDEOID
      if (url.pathname.includes('/embed/')) {
        const id = url.pathname.split('/embed/')[1].split('/')[0];
        return 'https://www.youtube.com/watch?v=' + id;
      }
      // youtu.be/VIDEOID
      if (/^(.+\.)?youtu\.be$/i.test(host)) {
        const id = url.pathname.replace(/^\/+/, '').split('/')[0];
        return 'https://www.youtube.com/watch?v=' + id;
      }
      // bereits watch?v=...
      if (/youtube\.com$/i.test(host) && url.searchParams.has('v')) {
        return url.origin + url.pathname + url.search;
      }
      return null;
    } catch (_) { return null; }
  }
  function ensureWrapperWithButton(el, btnLabel, onClick) {
    const parent = el.parentElement;
    if (!parent) return;

    // Eltern relativ positionieren
    if (!parent.classList.contains('slfs-ovl-wrap')) {
      parent.classList.add('slfs-ovl-wrap');
      const computed = window.getComputedStyle(parent).position;
      if (computed === 'static') parent.style.position = 'relative';
    }

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'slfs-ovl-btn';
    btn.textContent = btnLabel;
    btn.addEventListener('click', (ev) => { ev.preventDefault(); onClick(); });

    parent.appendChild(btn);
  }

  function makeFsAnchor(href, extraAttrs = {}) {
    const a = document.createElement('a');
    a.setAttribute('data-fslightbox', GALLERY_KEY);
    a.setAttribute('href', href);
    for (const [k, v] of Object.entries(extraAttrs)) {
      a.setAttribute(k, v);
    }
    a.classList.add('slfs-hidden-link');
    return a;
  }

  // ----------- Start / Stop Videos Handlers -----------------
  /**
     * @function stopAllYouTubeVideos
     * @description Stops all running YouTube videos in all iframes on the page using postMessage.
     * This function finds all iframes on the page and sends a postMessage to each iframe with the command to stop the video playback.
     *
     * @returns {void}
     */
  function stopAllYouTubeVideos() {
      // Find all iframes on the page
      const iframes = getIFrames();

      // Iterate over each iframe
      for (const iframe of iframes) {
          // Check if the iframe is a YouTube video
          if (iframe.src.includes('youtube.com') || iframe.src.includes('youtube-nocookie.com')) {
              // Send a postMessage to the iframe to stop the video playback
              if (iframe.contentWindow) {
                iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
              }
          }
      }
  }

  function stopAllHTMLVideos() {
    const videos = document.querySelectorAll('video');
    videos.forEach(v => { try { v.pause(); } catch (_) {} });
  }

  function handleOnOpen(instance) {
        // append enablejsapi to youtube src in fslightbox
        var sources = instance.elements.sources;
        lastSlideIndex = instance.stageIndexes.current;

        for (let source of sources) {
            if (source && source.tagName === "IFRAME") {
                let newSrc = '';
                
                if (!source.src.includes('enablejsapi')) {
                    newSrc = normalizeYouTubeIframeUrl(source.src);
                    source.src = newSrc;
                }
                if (source.src.includes('youtube') && !source.src.includes('nocookie')) {
                    newSrc = source.src.replace('youtube', 'youtube-nocookie')
                    source.src = newSrc;
                }
            }
        }

        // stop all running YouTube and HTML videos
        try {
            // Call the function to stop all YouTube videos
            stopAllYouTubeVideos();
            // stop other running videos
            stopAllHTMLVideos();
        } catch (error) {
            // If there's an error, log the error message
            console.error(error.message);
        }
  }

  function handleOnSlideChange(instance) {
    stopAllYouTubeVideos();
    stopAllHTMLVideos();
  }

  function getIFrames() {  
    if (iframes !== null) {  
        return iframes;  
    } 
    return Array.from(elements).filter(element => element.tagName === 'IFRAME');  
  }

  function getVideos() { 
    if (videos !== null) {  
        return videos;  
    } 
    return Array.from(elements).filter(element => element.tagName === 'VIDEO');  
  } 

  // ------------- Init (or main) ---------------
  // Init
  // ----------------------------
  function init() {
    const settings = loadSettings();
    const root = settings.scanBody === true ? document.body : document.body; // optional könnte hier nach dem ':' #primary .entry-content o.ä. gewählt werden  
    elements = root.querySelectorAll('img, video, iframe');
    NElements = elements.length;
    if (NElements === 0) return;

    iframes = getIFrames();
    NIframes = iframes.length;
    videos = getVideos();
    NVideos = videos.length;

    
    NIframes ? adoptYTIframeLinks() : null;
    
    collectAndWire(settings);

    // Events an die konkrete Instanz hängen (Schlüssel = data-fslightbox-Wert)
    try {
      const inst = window.fsLightboxInstances[GALLERY_KEY];

      if (inst && inst.props && (NIframes > 0 || NVideos > 0)) {
        inst.props.onOpen = handleOnOpen;     // Event vorhanden in Free+Pro
        inst.props.onSlideChange = handleOnSlideChange; // onSlideChange ist nur in paid verfügbar
      }
    } catch (e) {
      console.error('[SLFS] Event-Init failed:', e);
    }
  }

  // ------------- DOM READY -------------
  // Asynchrones Ausführen der init-Funktion  
  window.addEventListener('load', () => {  
      // Verwende setTimeout mit 0 ms Verzögerung, um die init-Funktion asynchron auszuführen  
      setTimeout(init, 0);  
        
      // Alternativ: Verwende requestAnimationFrame, um sicherzustellen, dass die Initialisierung nach dem nächsten Repaint stattfindet  
      // requestAnimationFrame(init);  
  });
  /*
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
  */
})(window, document);

