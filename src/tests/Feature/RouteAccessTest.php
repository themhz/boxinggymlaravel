<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RouteAccessTest extends TestCase
{
    /**
     * A basic feature test example.
     */    
    use RefreshDatabase; 
    #[\PHPUnit\Framework\Attributes\Test]
    public function homepage_route_is_accessible()
    {
        $this->get('/api/homepage')->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
     public function about_route_is_accessible()
     {
         $this->get('/api/about')->assertStatus(200);
     }

    #[\PHPUnit\Framework\Attributes\Test]
    public function classes_route_is_accessible()
    {
        $this->get('/api/classes')->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pricing_route_is_accessible()
    {
        $this->get('/api/pricing')->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function appointments_availability_route_is_accessible()
    {
        $this->get('/api/appointments/availability')->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function contact_info_route_is_accessible()
    {
        $this->get('/api/contact-info')->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function classes_available_route_is_accessible()
    {
        $this->get('/api/classes/available')->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function teams_index_route_is_accessible()
    {
        $this->get('/api/teams')->assertStatus(200);
    }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function teachers_index_route_is_accessible()
    // {
    //     $this->get('/api/teachers')->assertStatus(200);
    // }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function students_index_route_is_accessible()
    // {
    //     $this->get('/api/students')->assertStatus(200);
    // }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function class_types_index_route_is_accessible()
    // {
    //     $this->get('/api/class-types')->assertStatus(200);
    // }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function programs_index_route_is_accessible()
    // {
    //     $this->get('/api/programs')->assertStatus(200);
    // }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function appointments_index_route_is_accessible()
    // {
    //     $this->get('/api/appointments')->assertStatus(200);
    // }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function posts_index_route_is_accessible()
    // {
    //     $this->get('/api/posts')->assertStatus(200);
    // }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function availability_index_route_is_accessible()
    // {
    //     $this->get('/api/availability')->assertStatus(200);
    // }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function api_routes_listing_is_accessible()
    // {
    //     $this->get('/api/routes')->assertStatus(200);
    // }
}
