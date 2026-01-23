<?php
/**
 * PHPUnit Test Suite Setup
 */

namespace Modular\Tests;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase {
    protected $db;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Setup test database
        $this->db = new \PDO(
            "mysql:host=127.0.0.1;dbname=modular_test",
            "root",
            "root",
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }
    
    protected function tearDown(): void {
        parent::tearDown();
    }
}


