<?php
/**
 * Example Test
 */

namespace Modular\Tests;

use Modular\Cache;

class CacheTest extends BaseTestCase {
    public function testCacheSetAndGet() {
        $cache = new Cache();
        $cache->set('test_key', 'test_value', 60);
        
        $this->assertEquals('test_value', $cache->get('test_key'));
    }
    
    public function testCacheExpiration() {
        $cache = new Cache();
        $cache->set('test_key', 'test_value', 1);
        
        sleep(2);
        
        $this->assertNull($cache->get('test_key'));
    }
}


