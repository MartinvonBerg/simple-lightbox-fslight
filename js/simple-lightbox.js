(function (window, document, undefined) {
    "use strict";
    let lastSlideIndex = 0;

    /** Random Error message in console of browser:
     * The service worker navigation preload request was cancelled before 'preloadResponse' settled. If you intend to use 'preloadResponse', use waitUntil() or respondWith() to wait for the promise to settle.
    */

    /**
     * @function stopAllYouTubeVideos
     * @description Stops all running YouTube videos in all iframes on the page using postMessage.
     * This function finds all iframes on the page and sends a postMessage to each iframe with the command to stop the video playback.
     *
     * @returns {void}
     */
    function stopAllYouTubeVideos() {
        // Find all iframes on the page
        const iframes = document.querySelectorAll('iframe');

        // Iterate over each iframe
        for (const iframe of iframes) {
            // Check if the iframe is a YouTube video
            if (iframe.src.includes('youtube.com') || iframe.src.includes('youtube-nocookie.com')) {
                // Send a postMessage to the iframe to stop the video playback
                iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
            }
        }
    }

    /**
     * @function stopAllHTMLVideos
     * @description Pauses all HTML videos on the page.
     *
     * @return {void} Does not return a value.
     */
    function stopAllHTMLVideos() {
        // Find all videos on the page
        const videos = document.querySelectorAll('video');

        // Iterate over each video
        for (const video of videos) {
            video.pause();
        }
    }

    /**
     * @function handleOnOpen
     * @description Pauses all running YouTube videos in all iframes on the page using stopAllYouTubeVideos. Appends additionally the enablejsapi=1 to the src tag of the iframe.
     * 
     * @parameter {Object} instance - The fslightbox instance
     * @returns {void}
     */
    function handleOnOpen(instance) {
        // append enablejsapi to youtube src in fslightbox
        var sources = instance.elements.sources;
        lastSlideIndex = instance.stageIndexes.current;

        for (let source of sources) {
            if (source && source.tagName === "IFRAME") {
                let oldSrc = source.src;
                if (!oldSrc.includes('enablejsapi')) {
                    oldSrc = oldSrc + (oldSrc.includes('?') ? '' : '?') + 'enablejsapi=1';
                    source.src = oldSrc;
                }
                if (oldSrc.includes('youtube') && !oldSrc.includes('nocookie')) {
                    oldSrc = oldSrc.replace('youtube', 'youtube-nocookie')
                    source.src = oldSrc;
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

    /**
     * @function handleOnSlideChange
     * @description A function that pauses a running youtube video in opened fslightbox on slide change with postMessage
     * 
     * @returns {void}
     */
    function handleOnSlideChange() {
        stopAllYouTubeVideos();
        stopAllHTMLVideos();
    }
    
    try {
        fsLightboxInstances['1'].props.onOpen = handleOnOpen;
        fsLightboxInstances['1'].props.onSlideChange = handleOnSlideChange; // not available in free version of fslightbox
    } catch (error) {
        // If there's an error, log the error message
        console.error(error.message);
    }

})(window, document);
