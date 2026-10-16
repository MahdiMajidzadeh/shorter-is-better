<?php

namespace Tests\Feature;

use Tests\TestCase;
use RuntimeException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FailedJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_jobs_are_logged_to_the_database(): void
    {
        app('queue.failer')->log('database', 'default', '{"uuid":"8f3c2a","job":"x"}', new RuntimeException('boom'));

        $this->assertCount(1, app('queue.failer')->all());
    }
}
