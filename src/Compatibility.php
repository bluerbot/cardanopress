<?php

/**
 * @package ThemePlate
 * @since   0.1.0
 */

namespace PBWebDev\CardanoPress;

use CardanoPress\Traits\Instantiable;
use CardanoPress\Traits\Loggable;
use Psr\Log\LoggerInterface;

class Compatibility
{
    use Instantiable;
    use Loggable;

    protected array $messages = [];
    protected array $issues = [];

    public const DATA_PREFIX = 'cardanopress_';

    public function __construct(LoggerInterface $logger)
    {
        $this->issues = $this->getIssues(true);
        $this->messages = [
            'server' => __('WebAssembly MIME type is not supported by the server.', 'cardanopress'),
            'theme' => __('Incomplete template injections in front-end.', 'cardanopress'),
            'classic' => __('Activated theme does not support the `wp_body_open` hook.', 'cardanopress'),
            'block' => __('Block theme does not fully work with the provided page templates.', 'cardanopress'),
            'default' => __('By default, each pages will be loaded as per theme\'s `page.html` specification.', 'cardanopress'),
            'content' => __('The shortcodes to layout the page needs to be manually added in the content editor.', 'cardanopress'),
            'ignore' => __('Please ignore if custom templates are already implemented, else they will be rendered blank.', 'cardanopress'),
        ];

        $this->setLogger($logger);
        $this->setInstance($this);
    }

    public function getStatus()
    {
        return get_option(static::DATA_PREFIX . 'status', 'normal');
    }

    public function setStatus(string $state): bool
    {
        return update_option(static::DATA_PREFIX . 'status', $state, false);
    }

    protected function theme(): bool
    {
        $url = home_url();
        $args = [
            'timeout' => apply_filters('http_request_timeout', MINUTE_IN_SECONDS, $url),
            'sslverify' => apply_filters('https_local_ssl_verify', false),
        ];

        return ! is_wp_error(wp_remote_get($url, $args));
    }

    protected function server(): bool
    {
        $url = plugin_dir_url(Application::getInstance()->getPluginFile());
        $args = [
            'timeout' => apply_filters('http_request_timeout', MINUTE_IN_SECONDS, $url),
            'sslverify' => apply_filters('https_local_ssl_verify', false),
        ];

        $response = wp_remote_head($url . 'src/test.wasm', $args);

        if (is_wp_error($response)) {
            return $this->server();
        }

        return ('application/wasm' === wp_remote_retrieve_header($response, 'content-type'));
    }

    public function message(string $type): string
    {
        return $this->messages[$type] ?? '';
    }

    public function addIssue(string $type): bool
    {
        if (! in_array($type, array_keys($this->messages), true)) {
            return false;
        }

        $this->issues[] = $type;

        return true;
    }

    public function getIssues(bool $in_cache = false): array
    {
        if ($in_cache) {
            wp_cache_delete(static::DATA_PREFIX . 'issues', 'options');

            return get_option(static::DATA_PREFIX . 'issues', []);
        }

        return array_unique($this->issues);
    }

    public function saveIssues(bool $reset = false): bool
    {
        $issues = $this->getIssues();

        if ($reset) {
            $issues = [];
            $this->issues = [];
        }

        return update_option(static::DATA_PREFIX . 'issues', $issues, false);
    }

    public function run(): void
    {
        $this->setStatus('checking');

        if (! $this->server()) {
            $this->addIssue('server');
        }

        if (wp_is_block_theme()) {
            $this->addIssue('block');
        }

        if (! $this->theme()) {
            $this->setStatus('activated');

            return;
        }

        foreach ($this->getIssues(true) as $issue) {
            $this->addIssue($issue);
        }

        $issues = $this->getIssues();

        foreach ($issues as $issue) {
            $this->log($this->message($issue), 'warning');
        }

        $this->saveIssues();
        $this->setStatus(empty($issues) ? 'normal' : 'issue');
    }
}
