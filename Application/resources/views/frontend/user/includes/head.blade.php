<!-- Passive Event Listener Polyfill - Must be first script! -->
<script>
(function() {
    // Check if passive events are supported
    var supportsPassive = false;
    try {
        var opts = Object.defineProperty({}, 'passive', {
            get: function() { supportsPassive = true; return true; }
        });
        window.addEventListener('testPassive', null, opts);
        window.removeEventListener('testPassive', null, opts);
    } catch (e) {}
    
    // If supported, patch addEventListener to make scroll events passive by default
    if (supportsPassive) {
        var originalAddEventListener = EventTarget.prototype.addEventListener;
        EventTarget.prototype.addEventListener = function(type, listener, options) {
            var passiveEvents = ['touchstart', 'touchmove', 'touchend', 'wheel', 'mousewheel', 'scroll'];
            var newOptions = options;
            
            if (passiveEvents.indexOf(type) !== -1) {
                if (typeof options === 'undefined' || options === false || options === null) {
                    newOptions = { passive: true };
                } else if (typeof options === 'object' && options.passive === undefined) {
                    newOptions = Object.assign({}, options, { passive: true });
                }
            }
            
            return originalAddEventListener.call(this, type, listener, newOptions);
        };
    }
})();
</script>

@include('frontend.configurations.metaTags')
<title>{{ pageTitle($__env) }}</title>
<link rel="apple-touch-icon-precomposed" href="{{ asset($settings['website_favicon']) }}"/>
<link rel="icon" href="{{ asset($settings['website_favicon']) }}" sizes="16x16 32x32 48x48 64x64"type="image/vnd.microsoft.icon"/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700&display=swap">
