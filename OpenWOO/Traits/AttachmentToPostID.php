<?php

namespace OpenWOO\Traits;

trait AttachmentToPostID
{
    protected function saveGravityFormsURLToMediaLibrary(string $url): int
    {
        $this->disableVerifySSL();
        $this->allowExtensionPDF();
        $this->requireCoreIncludes();

        $mediaId = \media_sideload_image($url, 0, null, 'id');

        if (\is_wp_error($mediaId)) {
            return 0;
        }

        return $mediaId;
    }

    private function allowExtensionPDF(): void
    {
        \add_filter('image_sideload_extensions', function ($extensions, $file) {
            array_push($extensions, 'pdf');
            return $extensions;
        }, 10, 2);
    }

    /**
     * Environments other than production have self signed SSL certificates.
     * Disable the verification of this type of certificates.
     */
    private function disableVerifySSL(): void
    {
        if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
            return;
        }

        \add_filter('http_request_args', function ($r, $url) {
            $r['sslverify'] = false;
            return $r;
        }, 10, 2);
    }

    /**
     * Includes are required for usage of the 'media_sideload_image' method.
     */
    private function requireCoreIncludes(): void
    {
        require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        require_once(ABSPATH . '/wp-admin/includes/media.php');
        require_once(ABSPATH . '/wp-admin/includes/file.php');
        require_once(ABSPATH . '/wp-admin/includes/image.php');
    }
}
