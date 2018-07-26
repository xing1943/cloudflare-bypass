<?php 

namespace CloudflareBypass\Tests;
 
use CloudflareBypass\RequestMethod\CFCurl;

class CurlTest extends TestCase
{
    /**
     * Urls to test
     *
     * @var string
     */
    protected $urls = [
        "http://thebot.net/",
        "http://dll.anime47.com/",
        "http://predb.me/?search=test",
        "http://torrentz2.eu/"
    ];
    
    /**
     * Test 503 (without bypass)
     *
     * @return void
     */
    public function test503()
    {
        foreach ($this->urls as $url) {
            // Make sure each site is protected by CF.
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_PROXY, $this->getProxyServer());
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            curl_setopt($ch, CURLOPT_USERAGENT, $this->getAgent());
            curl_exec($ch);

            $this->assertEquals($url.": "."503", $url.": ".curl_getinfo($ch, CURLINFO_HTTP_CODE));
        }
    }

    /**
     * Test 200 (with bypass)
     *
     * @return void
     */
    public function test200WithCache()
    {
        // Initialize CFCurl wrapper.
        $wrapper = new CFCurl(array(
            'cache'         => true,
            'cache_path'    => __DIR__."/../var/cache",
            'verbose'       => true
        ));

        foreach ($this->urls as $url) {

            // Parse url into components.
            $url_components = parse_url($url);

            // Get cache file (path included).
            $cache_file = __DIR__ . '/../var/cache/' . md5($url_components['host']);
            file_exists($cache_file) && unlink($cache_file);

            // Bypass each site using CFCurl wrapper.
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_PROXY, $this->getProxyServer());
            curl_setopt($ch, CURLOPT_USERAGENT, $this->getAgent());

            $response = $wrapper->exec($ch);

            $this->assertEquals($url.": "."200", $url.": ".curl_getinfo($ch, CURLINFO_HTTP_CODE));
            $this->assertEquals(true, file_exists($cache_file));
            $this->assertEquals(true, isset(json_decode(file_get_contents($cache_file))->cf_clearance));

            // Remove the file from cache.
            unlink($cache_file);

            curl_close($ch);
        }
    }

    /**
     * Test 200 (with bypass)
     *
     * @return void
     */
    public function test200WithNoCache()
    {
        $wrapper = new CFCurl(array(
            'cache'         => false,
            'verbose'       => true
        ));

        foreach ($this->urls as $url) {

            // Bypass each site using CFCurl wrapper.
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_PROXY, $this->getProxyServer());
            curl_setopt($ch, CURLOPT_USERAGENT, $this->getAgent());

            $response = $wrapper->exec($ch);

            $this->assertEquals($url.": "."200", $url.": ".curl_getinfo($ch, CURLINFO_HTTP_CODE));

            curl_close($ch);
        }
    }
}
