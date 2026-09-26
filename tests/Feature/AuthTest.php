<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AuthTest extends TestCase{use RefreshDatabase;public function test_customer_can_register_and_is_asked_to_verify_email():void{$r=$this->post('/register',['name'=>'Customer Test','email'=>'customer@example.com','password'=>'test1234','password_confirmation'=>'test1234']);$r->assertRedirect(route('verification.notice'));$this->assertDatabaseHas('users',['email'=>'customer@example.com','role'=>'customer']);}}
