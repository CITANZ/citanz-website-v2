(function(window) {
    var listeners = [],
    doc = window.document,
    MutationObserver = window.MutationObserver || window.WebKitMutationObserver,
    observer;

    function domready(selector, fn) {
        // Store the selector and callback to be monitored
        listeners.push({
            selector: selector,
            fn: fn
        });
        if (!observer) {
            // Watch for changes in the document
            observer = new MutationObserver(check);
            observer.observe(doc.documentElement, {
                childList: true,
                subtree: true
            });
        }
        // Check if the element is currently in the DOM
        check();
    }

    function check() {
        // Check the DOM for elements matching a stored selector
        for (var i = 0, len = listeners.length, listener, elements; i < len; i++) {
            listener = listeners[i];
            // Query for elements matching the specified selector
            elements = doc.querySelectorAll(listener.selector);
            for (var j = 0, jLen = elements.length, element; j < jLen; j++) {
                element = elements[j];
                // Make sure the callback isn't invoked with the
                // same element more than once
                if (!element.ready) {
                    element.ready = true;
                    // Invoke the callback with the element
                    listener.fn.call(element, element);
                }
            }
        }
    }

    var regex = /((\&|\?)vid=[0-9]+)/g;

    //checkForAWS and vid
    function checkForAWS(el){
        if(el.src && el.src.indexOf('AWS4') !== false){
            el.src = el.src.replace(regex, '');
        } else if(el.style && el.style.backgroundImage && el.style.backgroundImage.indexOf('AWS') !== false){
            el.style.backgroundImage = el.style.backgroundImage.replace(regex, '');
        }
    }
    domready('img', function(el) {
        checkForAWS(el);
    });

    domready('.gallery-item__thumbnail', function(el) {
        checkForAWS(el);
    });

    var images = document.querySelectorAll('img');
    for(var i = 0; i < images.length; i++){
        checkForAWS(images[i]);
    }

    var thumbnails = document.querySelectorAll('.gallery-item__thumbnail');
    for(var i = 0; i < thumbnails.length; i++){
        checkForAWS(thumbnails[i]);
    }
})(window);
