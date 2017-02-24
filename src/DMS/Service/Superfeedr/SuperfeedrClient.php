<?php
namespace DMS\Service\Superfeedr;

class SuperfeedrClient extends \GuzzleHttp\Client
{
    /**
     * @var string
     */
    protected $hubSecret;

    /**
     * @param array $username
     * @param $password
     * @param $secret
     * @param array $config
     */
    public function __construct($username, $password, $secret, array $config = [])
    {
        $this->hubSecret = sha1($secret);

        $defaultConfig = [
            'base_uri' => 'https://push.superfeedr.com',
            'auth' => [$username, $password],
            'debug' => fopen('php://output','w')
        ];

        parent::__construct(array_merge_recursive($defaultConfig, $config));
    }

    /**
     * @param $feed
     * @param $callbackUrl
     * @param string $format
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    public function subscribeFeed($feed, $callbackUrl, $format = 'json')
    {
        $options = [
            'form_params' => [
                'hub.mode'      => 'subscribe',
                'hub.topic'     => $feed,
                'hub.callback'  => $callbackUrl,
                'hub.secret'    => $this->hubSecret,
                'format'        => $format,
            ]
        ];

        return $this->post('/', $options);
    }

    /**
     * @param string $feed
     * @param string|null $callbackUrl
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    public function unsubscribeFeed($feed, $callbackUrl = null)
    {
        $options = [
            'form_params' => [
                'hub.mode'     => 'unsubscribe',
                'hub.topic'    => $feed,
            ]
        ];

        if ($callbackUrl !== null) {
            $options['form_params']['hub.callback'] = $callbackUrl;
        }

        return $this->post('/', $options);
    }

    /**
     * @param string $callbackUrl
     * @param int $page
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    public function listFeeds($callbackUrl, $page = 1)
    {
        $options = [
            'form_params' => [
                'hub.mode'     => 'list',
                'hub.callback' => $callbackUrl,
                'page'         => $page
            ]
        ];

        return $this->get('/', $options);
    }

    /**
     * @param string $feed
     * @param array $config Extra configs like: count, before, after, format, callback
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    public function retrieveFeeds($feed, array $config = [])
    {
        $options = [
            'form_params' => [
                'hub.mode'     => 'retrieve',
                'hub.topic'    => $feed,
                'format' => 'json'
            ]
        ];

        array_merge($options['form_params'], $config);

        return $this->get('/', $options);
    }

    /**
     * Checks the call to see if it was signed by Superfeedr
     * @param string $signature
     * @param mixed $content
     * @return bool
     */
    public function validateRequest($signature, $content)
    {
        $sum = hash_hmac('sha1', $content, $this->hubSecret);

        return ($signature === $sum);
    }
}
