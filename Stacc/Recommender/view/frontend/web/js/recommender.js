// AJAX function for handling, that will be called in container.pthml
function staccRecommender(url, id) {
    var element;
    element = document.getElementById(id);
    var req = new XMLHttpRequest();
    req.open('GET', url, true);
    req.onload = function (e) {
        if (req.readyState === 4) {
            if (req.status === 200) {
                if (element && req.responseText) {
                    element.innerHTML = req.responseText;
                    var event = new CustomEvent("staccRecsLoaded", {detail: {"element": element, "blockId": id}});
                    document.dispatchEvent(event);
                }
            }
        }
    };
    req.onerror = function (e) {
    };
    req.send(null);
}

require(['jquery'], function (jQuery) {
    document.addEventListener("staccRecsLoaded", function (data) {
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(
                function () {
                    if (typeof tns !== 'undefined') {
                        if (jQuery("#" + data.detail.blockId + " .stacc_recommender_controls").length > 0) {
                            var slider = tns({
                                container: '.stacc_recommender_slider',
                                items: 3,
                                slideBy: 'page',
                                autoplay: false,
                                rewind: true,
                                loop: false,
                                mouseDrag: true,
                                navContainer: false,
                                controlsContainer: "#" + data.detail.blockId + " .stacc_recommender_controls",
                                responsive:
                                    {
                                        "300": {
                                            "items": 2
                                        },
                                        "400": {
                                            "items": 2
                                        },
                                        "500": {
                                            "items": 3
                                        },
                                        "700": {
                                            "items": 5
                                        }
                                    }
                            });
                        }
                    } else {
                        console.info("Tiny-slider-min.js not found");
                    }
                }
            )
        } else {
            console.info("jQuery not found! jQuery is required for the tiny-slider carousel plugin.");
        }
    });

    (function () {
        // Support for CustomEvent for older browsers: <IE11, <Safari 10
        if (typeof window.CustomEvent === "function") return false;

        function CustomEvent(event, params) {
            params = params || {bubbles: false, cancelable: false, detail: undefined};
            var evt = document.createEvent('CustomEvent');
            evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
            return evt;
        }

        CustomEvent.prototype = window.Event.prototype;

        window.CustomEvent = CustomEvent;
    })();
});