<div id="install-pwa-button" class="install-pwa-button alert alert-warning alert-dismissible m15 mb0 p0">
    <div id="install-pwa" class="clickable p15">
        <i data-feather='smartphone' class='icon mr10'></i> <?php echo js_anchor(app_lang("install_this_app")); ?>
    </div>
    <?php echo js_anchor("", array("class" => "btn-close", "id" => "close-install-pwa")); ?>
</div>

<div style='display: none;'>
    <script type='text/javascript'>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register("<?php echo get_uri('pwa/service_worker'); ?>");
            });
        }

        let deferredPrompt;

        // Listen for the beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent the default browser prompt from showing
            e.preventDefault();
            // Store the event to be used later
            deferredPrompt = e;
        });

        // Add a click event listener to the install button
        document.getElementById('install-pwa').addEventListener('click', () => {
            if (deferredPrompt) {
                // Show the installation prompt
                deferredPrompt.prompt();
                // Wait for the user to respond to the prompt
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    // Clear the deferred prompt
                    deferredPrompt = null;
                });
            }
        });

        // Function to check if the app is installed
        function checkIfAppIsInstalled() {
            // For iOS
            if (window.navigator.standalone) {
                return true;
            }
            // For other platforms
            if (window.matchMedia('(display-mode: standalone)').matches) {
                return true;
            }
            return false;
        }

        // Hide the button if the app is installed
        if (checkIfAppIsInstalled()) {
            document.getElementById('install-pwa-button').style.display = 'none';
        }

        $('#close-install-pwa').on('click', function() {
            if ($("#install-pwa-button").length) {
                $("#install-pwa-button").remove();
            }
        });
    </script>
</div>